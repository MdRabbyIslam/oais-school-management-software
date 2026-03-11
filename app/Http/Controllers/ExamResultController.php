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
            ];
        }

        return $subjectRows;
    }
}
