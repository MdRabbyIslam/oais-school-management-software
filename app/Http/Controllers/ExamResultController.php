<?php

namespace App\Http\Controllers;

use App\Models\ExamAssessmentClass;
use App\Models\ExamStudentResult;
use App\Models\StudentEnrollment;
use App\Services\Exam\ExamResultService;
use Barryvdh\DomPDF\Facade\Pdf;

class ExamResultController extends Controller
{
    protected ExamResultService $resultService;

    public function __construct(ExamResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    public function publish(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Set assessment status to Published to publish results.');
        }

        $summary = $this->resultService->publish($examAssessmentClass, (int) auth()->id());

        return redirect()
            ->route('exam-assessment-classes.results.index', $examAssessmentClass)
            ->with('success', "Results published. Processed {$summary['processed']} student(s), skipped {$summary['skipped']}.");
    }

    public function index(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');

        $examAssessmentClass->load(['examAssessment', 'schoolClass']);
        $assessmentClasses = $examAssessmentClass->examAssessment
            ->assessmentClasses()
            ->with('schoolClass')
            ->get()
            ->sortBy(fn ($item) => (int) optional($item->schoolClass)->class_level)
            ->values();

        $results = ExamStudentResult::with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderBy('position')
            ->paginate(50);

        return view('pages.exams_results_index', compact('examAssessmentClass', 'assessmentClasses', 'results'));
    }

    public function show(ExamAssessmentClass $examAssessmentClass, StudentEnrollment $studentEnrollment)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result view is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment', 'schoolClass', 'assessmentSubjects.subject', 'assessmentSubjects.components']);
        $studentEnrollment->load('student');

        $result = ExamStudentResult::where('assessment_class_id', $examAssessmentClass->id)
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->firstOrFail();

        $subjectRows = $this->buildSubjectRows($examAssessmentClass, $studentEnrollment->id);

        return view('pages.exams_result_show', compact('examAssessmentClass', 'studentEnrollment', 'result', 'subjectRows'));
    }

    public function download(ExamAssessmentClass $examAssessmentClass, StudentEnrollment $studentEnrollment)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result download is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment', 'schoolClass', 'assessmentSubjects.subject', 'assessmentSubjects.components']);
        $studentEnrollment->load('student');

        $result = ExamStudentResult::where('assessment_class_id', $examAssessmentClass->id)
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->firstOrFail();

        $subjectRows = $this->buildSubjectRows($examAssessmentClass, $studentEnrollment->id);

        $pdf = Pdf::loadView('pages.exams_result_pdf', compact('examAssessmentClass', 'studentEnrollment', 'result', 'subjectRows'));

        return $pdf->download("result-{$studentEnrollment->id}-assessment-{$examAssessmentClass->id}.pdf");
    }

    public function downloadClassPdf(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result download is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment', 'schoolClass']);

        $results = ExamStudentResult::with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderBy('position')
            ->orderByDesc('percentage')
            ->get();

        $pdf = Pdf::loadView('pages.exams_results_full_pdf', compact('examAssessmentClass', 'results'));

        return $pdf->download("class-result-{$examAssessmentClass->id}.pdf");
    }

    private function buildSubjectRows(ExamAssessmentClass $assessmentClass, int $enrollmentId): array
    {
        $subjectRows = [];
        foreach ($assessmentClass->assessmentSubjects as $assessmentSubject) {
            $mark = $assessmentSubject->marks()
                ->with('components.assessmentSubjectComponent')
                ->where('student_enrollment_id', $enrollmentId)
                ->first();

            $subjectRows[] = [
                'subject' => $assessmentSubject->subject->name ?? "Subject #{$assessmentSubject->subject_id}",
                'total_marks' => $assessmentSubject->total_marks,
                'pass_marks' => $assessmentSubject->pass_marks,
                'obtained_marks' => $mark?->marks_obtained,
                'is_absent' => (bool) ($mark?->is_absent ?? false),
                'components' => $mark?->components ?? collect(),
                'is_pass' => $this->isSubjectPassForView($assessmentSubject, $mark),
            ];
        }

        return $subjectRows;
    }

    private function isSubjectPassForView($assessmentSubject, $mark): bool
    {
        $obtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
            ? (float) $mark->marks_obtained
            : 0.0;

        if ($obtained < (float) $assessmentSubject->pass_marks) {
            return false;
        }

        if ($assessmentSubject->components->isEmpty()) {
            return true;
        }

        $componentMarks = $mark?->components?->keyBy('assessment_subject_component_id') ?? collect();
        foreach ($assessmentSubject->components as $componentSetup) {
            $passMark = (float) ($componentSetup->pass_marks ?? 0);
            $componentMark = $componentMarks->get($componentSetup->id);
            $componentObtained = ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null)
                ? (float) $componentMark->marks_obtained
                : null;

            if ($componentObtained === null || $componentObtained < $passMark) {
                return false;
            }
        }

        return true;
    }

    private function isResultOperationAllowed(ExamAssessmentClass $examAssessmentClass): bool
    {
        $status = $examAssessmentClass->relationLoaded('examAssessment')
            ? $examAssessmentClass->examAssessment->status
            : $examAssessmentClass->examAssessment()->value('status');

        return $status === 'published';
    }
}
