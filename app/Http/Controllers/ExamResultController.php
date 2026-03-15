<?php

namespace App\Http\Controllers;

use App\Models\ExamAssessmentClass;
use App\Models\ExamMark;
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

    public function printStudent(ExamAssessmentClass $examAssessmentClass, StudentEnrollment $studentEnrollment)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result print is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment', 'schoolClass', 'assessmentSubjects.subject', 'assessmentSubjects.components']);
        $studentEnrollment->load('student');

        $result = ExamStudentResult::where('assessment_class_id', $examAssessmentClass->id)
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->firstOrFail();

        $subjectRows = $this->buildSubjectRows($examAssessmentClass, $studentEnrollment->id);

        return view('pages.exams_result_print', compact('examAssessmentClass', 'studentEnrollment', 'result', 'subjectRows'));
    }

    public function downloadClassPdf(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result download is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment.academicYear', 'schoolClass']);

        $results = ExamStudentResult::with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderBy('position')
            ->orderByDesc('percentage')
            ->get();

        $assessmentSubjects = $examAssessmentClass->assessmentSubjects()
            ->with(['subject', 'components', 'gradingPolicy.gradeScheme.items'])
            ->orderBy('subject_id')
            ->get();

        $enrollmentIds = $results->pluck('student_enrollment_id')->values();
        $marksByKey = ExamMark::query()
            ->with('components')
            ->whereIn('assessment_subject_id', $assessmentSubjects->pluck('id'))
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->get()
            ->keyBy(fn ($mark) => $mark->assessment_subject_id . ':' . $mark->student_enrollment_id);

        $subjectLayouts = $assessmentSubjects->map(function ($assessmentSubject) {
            $components = $assessmentSubject->components
                ->filter(fn ($component) => !$this->isSkippedComponent($component->component_name, $component->component_code))
                ->values();

            $componentColumns = $components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'label' => $this->componentShortLabel($component->component_name, $component->component_code),
                    'pass_marks' => (float) ($component->pass_marks ?? 0),
                ];
            })->all();

            $hasRealComponents = count($componentColumns) > 0;

            return [
                'assessment_subject_id' => $assessmentSubject->id,
                'subject_name' => $assessmentSubject->subject->name ?? ('Subject #' . $assessmentSubject->subject_id),
                'total_marks' => $assessmentSubject->total_marks,
                'pass_marks' => (float) $assessmentSubject->pass_marks,
                'component_columns' => $componentColumns,
                'show_total_column' => $hasRealComponents,
            ];
        })->values();
        $gradeItemsByAssessmentSubject = $assessmentSubjects->mapWithKeys(function ($assessmentSubject) {
            return [
                $assessmentSubject->id => $assessmentSubject->gradingPolicy?->gradeScheme?->items
                    ? $assessmentSubject->gradingPolicy->gradeScheme->items->sortBy('sort_order')->values()
                    : collect(),
            ];
        });

        $rows = $results->map(function ($result) use ($subjectLayouts, $marksByKey, $gradeItemsByAssessmentSubject) {
            $subjectData = [];
            $hasFailedMandatorySubject = false;
            foreach ($subjectLayouts as $layout) {
                $mark = $marksByKey->get($layout['assessment_subject_id'] . ':' . $result->student_enrollment_id);
                $markComponents = $mark?->components?->keyBy('assessment_subject_component_id') ?? collect();

                $componentValues = [];
                foreach ($layout['component_columns'] as $componentColumn) {
                    $componentMark = $markComponents->get($componentColumn['id']);
                    $componentValues[] = ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null)
                        ? (float) $componentMark->marks_obtained
                        : null;
                }

                $obtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
                    ? (float) $mark->marks_obtained
                    : null;
                $subjectPass = $this->isSubjectPassForClassPdf($layout, $mark, $markComponents);
                if (!$subjectPass) {
                    $hasFailedMandatorySubject = true;
                }

                $subjectData[$layout['assessment_subject_id']] = [
                    'components' => $componentValues,
                    'total' => $obtained,
                    'gpa' => $subjectPass ? $this->resolveSubjectGpaForClassPdf(
                        $layout['assessment_subject_id'],
                        $marksByKey,
                        (int) $result->student_enrollment_id,
                        $gradeItemsByAssessmentSubject
                    ) : 0.0,
                ];
            }

            return [
                'roll' => $result->studentEnrollment->roll_number ?? '-',
                'student_name' => $result->studentEnrollment->student->name ?? ('Student #' . $result->student_enrollment_id),
                'subject_data' => $subjectData,
                'total' => (float) $result->total_obtained,
                'gpa' => $hasFailedMandatorySubject ? 0.0 : (float) $result->gpa,
                'position' => $result->position ?? '-',
            ];
        })->values();

        $pdf = Pdf::loadView('pages.exams_results_full_pdf', compact(
            'examAssessmentClass',
            'subjectLayouts',
            'rows'
        ))->setPaper('a4', 'landscape');

        return $pdf->download("class-result-{$examAssessmentClass->id}.pdf");
    }

    public function printClass(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result print is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment.academicYear', 'schoolClass']);

        $results = ExamStudentResult::with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderBy('position')
            ->orderByDesc('percentage')
            ->get();

        $assessmentSubjects = $examAssessmentClass->assessmentSubjects()
            ->with(['subject', 'components', 'gradingPolicy.gradeScheme.items'])
            ->orderBy('subject_id')
            ->get();

        $enrollmentIds = $results->pluck('student_enrollment_id')->values();
        $marksByKey = ExamMark::query()
            ->with('components')
            ->whereIn('assessment_subject_id', $assessmentSubjects->pluck('id'))
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->get()
            ->keyBy(fn ($mark) => $mark->assessment_subject_id . ':' . $mark->student_enrollment_id);

        $subjectLayouts = $this->buildClassPrintLayouts($assessmentSubjects);
        $gradeItemsByAssessmentSubject = $assessmentSubjects->mapWithKeys(function ($assessmentSubject) {
            return [
                $assessmentSubject->id => $assessmentSubject->gradingPolicy?->gradeScheme?->items
                    ? $assessmentSubject->gradingPolicy->gradeScheme->items->sortBy('sort_order')->values()
                    : collect(),
            ];
        });

        $rows = $this->buildClassPrintRows($results, $subjectLayouts, $marksByKey, $gradeItemsByAssessmentSubject);

        return view('pages.exams_results_full_print', compact('examAssessmentClass', 'subjectLayouts', 'rows'));
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

    private function isSkippedComponent(?string $componentName, ?string $componentCode): bool
    {
        $normalizedName = strtoupper(trim((string) $componentName));
        $normalizedCode = strtoupper(trim((string) $componentCode));

        return in_array($normalizedName, ['A', 'AV', 'H'], true)
            || in_array($normalizedCode, ['A', 'AV', 'H'], true);
    }

    private function componentShortLabel(?string $componentName, ?string $componentCode): string
    {
        $name = strtoupper(trim((string) $componentName));
        if ($name === 'WRITTEN') {
            return 'W';
        }
        if ($name === 'MCQ') {
            return 'M';
        }
        if ($name === 'PRACTICAL') {
            return 'P';
        }

        if ($name !== '') {
            return substr($name, 0, 1);
        }

        $code = strtoupper(trim((string) $componentCode));
        return $code !== '' ? substr($code, 0, 1) : 'C';
    }

    private function resolveSubjectGpaForClassPdf(int $assessmentSubjectId, $marksByKey, int $enrollmentId, $gradeItemsByAssessmentSubject): float
    {
        $mark = $marksByKey->get($assessmentSubjectId . ':' . $enrollmentId);
        if (!$mark || $mark->is_absent || $mark->marks_obtained === null) {
            return 0.0;
        }

        $items = $gradeItemsByAssessmentSubject->get($assessmentSubjectId, collect());
        $obtained = (float) $mark->marks_obtained;

        $matched = $items->first(function ($item) use ($obtained) {
            return $obtained >= (float) $item->min_mark && $obtained <= (float) $item->max_mark;
        });

        return $matched ? (float) $matched->gpa : 0.0;
    }

    private function isSubjectPassForClassPdf(array $layout, ?ExamMark $mark, $markComponents): bool
    {
        $totalObtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
            ? (float) $mark->marks_obtained
            : 0.0;

        if ($totalObtained < (float) ($layout['pass_marks'] ?? 0)) {
            return false;
        }

        foreach ($layout['component_columns'] as $componentColumn) {
            $componentMark = $markComponents->get($componentColumn['id']);
            $componentObtained = ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null)
                ? (float) $componentMark->marks_obtained
                : null;

            $componentPass = (float) ($componentColumn['pass_marks'] ?? 0);
            if ($componentObtained === null || $componentObtained < $componentPass) {
                return false;
            }
        }

        return true;
    }

    private function buildClassPrintLayouts($assessmentSubjects)
    {
        return $assessmentSubjects->map(function ($assessmentSubject) {
            $components = $assessmentSubject->components
                ->filter(fn ($component) => !$this->isSkippedComponent($component->component_name, $component->component_code))
                ->values();

            $componentColumns = $components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'label' => $this->componentShortLabel($component->component_name, $component->component_code),
                    'pass_marks' => (float) ($component->pass_marks ?? 0),
                ];
            })->all();

            return [
                'assessment_subject_id' => $assessmentSubject->id,
                'subject_name' => $assessmentSubject->subject->name ?? ('Subject #' . $assessmentSubject->subject_id),
                'total_marks' => $assessmentSubject->total_marks,
                'pass_marks' => (float) $assessmentSubject->pass_marks,
                'component_columns' => $componentColumns,
                'show_total_column' => count($componentColumns) > 0,
            ];
        })->values();
    }

    private function buildClassPrintRows($results, $subjectLayouts, $marksByKey, $gradeItemsByAssessmentSubject)
    {
        return $results->map(function ($result) use ($subjectLayouts, $marksByKey, $gradeItemsByAssessmentSubject) {
            $subjectData = [];
            $hasFailedMandatorySubject = false;

            foreach ($subjectLayouts as $layout) {
                $mark = $marksByKey->get($layout['assessment_subject_id'] . ':' . $result->student_enrollment_id);
                $markComponents = $mark?->components?->keyBy('assessment_subject_component_id') ?? collect();

                $componentValues = [];
                foreach ($layout['component_columns'] as $componentColumn) {
                    $componentMark = $markComponents->get($componentColumn['id']);
                    $componentValues[] = ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null)
                        ? (float) $componentMark->marks_obtained
                        : null;
                }

                $obtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
                    ? (float) $mark->marks_obtained
                    : null;
                $subjectPass = $this->isSubjectPassForClassPdf($layout, $mark, $markComponents);
                if (!$subjectPass) {
                    $hasFailedMandatorySubject = true;
                }

                $subjectData[$layout['assessment_subject_id']] = [
                    'components' => $componentValues,
                    'total' => $obtained,
                    'gpa' => $subjectPass ? $this->resolveSubjectGpaForClassPdf(
                        $layout['assessment_subject_id'],
                        $marksByKey,
                        (int) $result->student_enrollment_id,
                        $gradeItemsByAssessmentSubject
                    ) : 0.0,
                ];
            }

            return [
                'roll' => $result->studentEnrollment->roll_number ?? '-',
                'student_name' => $result->studentEnrollment->student->name ?? ('Student #' . $result->student_enrollment_id),
                'subject_data' => $subjectData,
                'total' => (float) $result->total_obtained,
                'gpa' => $hasFailedMandatorySubject ? 0.0 : (float) $result->gpa,
                'position' => $result->position ?? '-',
            ];
        })->values();
    }
}
