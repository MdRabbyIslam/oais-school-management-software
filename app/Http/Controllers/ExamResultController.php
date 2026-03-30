<?php

namespace App\Http\Controllers;

use App\Models\ClassTestMark;
use App\Models\ClassTest;
use App\Models\ExamAssessmentClass;
use App\Models\ExamMark;
use App\Models\ExamStudentResult;
use App\Models\StudentTermExtraMark;
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

        $examAssessmentClass->load(['examAssessment', 'schoolClass', 'assessmentSubjects']);
        $assessmentClasses = $examAssessmentClass->examAssessment
            ->assessmentClasses()
            ->with('schoolClass')
            ->get()
            ->sortBy(fn ($item) => (int) optional($item->schoolClass)->class_level)
            ->values();

        $results = ExamStudentResult::query()
            ->select('exam_student_results.*')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'exam_student_results.student_enrollment_id')
            ->with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderByRaw('ISNULL(student_enrollments.roll_number), student_enrollments.roll_number ASC')
            ->paginate(50);
        $extraMarksByEnrollment = $this->extraMarksByEnrollment(
            $examAssessmentClass,
            $results->getCollection()->pluck('student_enrollment_id')->values()
        );

        $draftClassTestsCount = $this->draftClassTestsCountForAv($examAssessmentClass);

        return view('pages.exams_results_index', compact(
            'examAssessmentClass',
            'assessmentClasses',
            'results',
            'extraMarksByEnrollment',
            'draftClassTestsCount'
        ));
    }

    public function show(ExamAssessmentClass $examAssessmentClass, StudentEnrollment $studentEnrollment)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result view is available only when assessment status is Published.');
        }

        $examAssessmentClass->load([
            'examAssessment',
            'schoolClass',
            'assessmentSubjects.subject',
            'assessmentSubjects.components',
            'assessmentSubjects.gradingPolicy.gradeScheme.items',
        ]);
        $studentEnrollment->load(['student', 'optionalSubject']);

        $result = ExamStudentResult::where('assessment_class_id', $examAssessmentClass->id)
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->firstOrFail();

        $subjectRows = $this->buildSubjectRows($examAssessmentClass, $studentEnrollment);

        return view('pages.exams_result_show', compact('examAssessmentClass', 'studentEnrollment', 'result', 'subjectRows'));
    }

    public function download(ExamAssessmentClass $examAssessmentClass, StudentEnrollment $studentEnrollment)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result download is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment', 'schoolClass', 'assessmentSubjects.subject', 'assessmentSubjects.components']);
        $studentEnrollment->load(['student', 'optionalSubject']);

        $result = ExamStudentResult::where('assessment_class_id', $examAssessmentClass->id)
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->firstOrFail();

        $subjectRows = $this->buildSubjectRows($examAssessmentClass, $studentEnrollment);

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
        $studentEnrollment->load(['student', 'optionalSubject']);

        $result = ExamStudentResult::where('assessment_class_id', $examAssessmentClass->id)
            ->where('student_enrollment_id', $studentEnrollment->id)
            ->firstOrFail();

        $subjectRows = $this->buildSubjectRows($examAssessmentClass, $studentEnrollment);
        $extraMarks = $this->extraMarksByEnrollment($examAssessmentClass, collect([$studentEnrollment->id]))[$studentEnrollment->id] ?? [
            'homework_marks' => 0.0,
            'attendance_marks' => 0.0,
        ];
        $highestFinalMarksBySubject = $this->highestFinalMarksBySubject($examAssessmentClass);
        $printView = $this->isNurseryToClassTwo($examAssessmentClass)
            ? 'pages.exams_result_print'
            : 'pages.exams_result_print_secondary';
        // $printView = 'pages.exams_result_print';
        // $printView = 'pages.exams_result_print_secondary';

        return view($printView, compact(
            'examAssessmentClass',
            'studentEnrollment',
            'result',
            'subjectRows',
            'extraMarks',
            'highestFinalMarksBySubject'
        ));
    }

    public function downloadClassPdf(ExamAssessmentClass $examAssessmentClass)
    {
        $this->authorize('manage-exams');
        if (!$this->isResultOperationAllowed($examAssessmentClass)) {
            return back()->with('error', 'Result download is available only when assessment status is Published.');
        }

        $examAssessmentClass->load(['examAssessment.academicYear', 'schoolClass']);

        $results = ExamStudentResult::query()
            ->select('exam_student_results.*')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'exam_student_results.student_enrollment_id')
            ->with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderByRaw('ISNULL(student_enrollments.roll_number), student_enrollments.roll_number ASC')
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
        $classTestAverageMap = $this->buildClassTestAverageMapForAssessmentClass(
            $examAssessmentClass,
            $assessmentSubjects,
            $enrollmentIds
        );

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
                'assessment_subject' => $assessmentSubject,
                'assessment_subject_id' => $assessmentSubject->id,
                'subject_name' => $assessmentSubject->subject->name ?? ('Subject #' . $assessmentSubject->subject_id),
                'subject_id' => (int) $assessmentSubject->subject_id,
                'total_marks' => $assessmentSubject->total_marks,
                'pass_marks' => (float) $assessmentSubject->pass_marks,
                'component_columns' => $componentColumns,
                'show_total_column' => $hasRealComponents,
                'show_average_column' => true,
            ];
        })->values();
        $gradeItemsByAssessmentSubject = $assessmentSubjects->mapWithKeys(function ($assessmentSubject) {
            return [
                $assessmentSubject->id => $assessmentSubject->gradingPolicy?->gradeScheme?->items
                    ? $assessmentSubject->gradingPolicy->gradeScheme->items->sortBy('sort_order')->values()
                    : collect(),
            ];
        });

        $extraMarksByEnrollment = $this->extraMarksByEnrollment($examAssessmentClass, $enrollmentIds);
        $rows = $results->map(function ($result) use (
            $examAssessmentClass,
            $subjectLayouts,
            $marksByKey,
            $gradeItemsByAssessmentSubject,
            $classTestAverageMap,
            $extraMarksByEnrollment
        ) {
            $subjectData = [];
            $hasFailedMandatorySubject = false;
            $calculationMode = $this->resultCalculationMode($examAssessmentClass);
            foreach ($subjectLayouts as $layout) {
                $assessmentSubject = $layout['assessment_subject'] ?? null;
                if ($assessmentSubject && !$this->isSubjectApplicableToEnrollment($assessmentSubject, $result->studentEnrollment, $calculationMode)) {
                    $subjectData[$layout['assessment_subject_id']] = [
                        'components' => array_fill(0, count($layout['component_columns']), null),
                        'total' => null,
                        'average' => null,
                        'gpa' => null,
                    ];
                    continue;
                }

                $mark = $marksByKey->get($layout['assessment_subject_id'] . ':' . $result->student_enrollment_id);
                $markComponents = $mark?->components?->keyBy('assessment_subject_component_id') ?? collect();

                $componentValues = [];
                foreach ($layout['component_columns'] as $componentColumn) {
                    $componentMark = $markComponents->get($componentColumn['id']);
                    $componentValues[] = ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null)
                        ? (float) $componentMark->marks_obtained
                        : null;
                }

                $termObtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
                    ? (float) $mark->marks_obtained
                    : null;
                $average = (float) ($classTestAverageMap[$layout['subject_id'] . ':' . $result->student_enrollment_id] ?? 0.0);
                $finalObtained = min(
                    (float) ($layout['total_marks'] ?? 0),
                    (float) (($termObtained ?? 0) + $average)
                );
                $subjectPass = $this->isSubjectPassForClassPdf($layout, $mark, $markComponents, $finalObtained);
                if (
                    !$subjectPass
                    && $assessmentSubject
                    && $this->subjectCountsAsMandatory($assessmentSubject, $result->studentEnrollment, $calculationMode)
                ) {
                    $hasFailedMandatorySubject = true;
                }

                $subjectData[$layout['assessment_subject_id']] = [
                    'components' => $componentValues,
                    'total' => $termObtained,
                    'average' => $average,
                    'gpa' => $subjectPass ? $this->resolveSubjectGpaForClassPdf(
                        $layout['assessment_subject_id'],
                        $finalObtained,
                        $gradeItemsByAssessmentSubject
                    ) : 0.0,
                ];
            }

            $extra = $extraMarksByEnrollment[(int) $result->student_enrollment_id] ?? [
                'homework_marks' => 0.0,
                'attendance_marks' => 0.0,
            ];
            $homeworkMarks = (float) ($extra['homework_marks'] ?? 0.0);
            $attendanceMarks = (float) ($extra['attendance_marks'] ?? 0.0);

            return [
                'roll' => $result->studentEnrollment->roll_number ?? '-',
                'student_name' => $result->studentEnrollment->student->name ?? ('Student #' . $result->student_enrollment_id),
                'subject_data' => $subjectData,
                'homework_marks' => $homeworkMarks,
                'attendance_marks' => $attendanceMarks,
                'total' => (float) $result->total_obtained + $homeworkMarks + $attendanceMarks,
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

        $results = ExamStudentResult::query()
            ->select('exam_student_results.*')
            ->join('student_enrollments', 'student_enrollments.id', '=', 'exam_student_results.student_enrollment_id')
            ->with('studentEnrollment.student')
            ->where('assessment_class_id', $examAssessmentClass->id)
            ->orderByRaw('ISNULL(student_enrollments.roll_number), student_enrollments.roll_number ASC')
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
        $classTestAverageMap = $this->buildClassTestAverageMapForAssessmentClass(
            $examAssessmentClass,
            $assessmentSubjects,
            $enrollmentIds
        );

        $subjectLayouts = $this->buildClassPrintLayouts($assessmentSubjects);
        $gradeItemsByAssessmentSubject = $assessmentSubjects->mapWithKeys(function ($assessmentSubject) {
            return [
                $assessmentSubject->id => $assessmentSubject->gradingPolicy?->gradeScheme?->items
                    ? $assessmentSubject->gradingPolicy->gradeScheme->items->sortBy('sort_order')->values()
                    : collect(),
            ];
        });

        $rows = $this->buildClassPrintRows(
            $examAssessmentClass,
            $results,
            $subjectLayouts,
            $marksByKey,
            $gradeItemsByAssessmentSubject,
            $classTestAverageMap,
            $this->extraMarksByEnrollment($examAssessmentClass, $enrollmentIds)
        );

        return view('pages.exams_results_full_print', compact('examAssessmentClass', 'subjectLayouts', 'rows'));
    }

    private function buildSubjectRows(ExamAssessmentClass $assessmentClass, StudentEnrollment $studentEnrollment): array
    {
        $subjectRows = [];
        $calculationMode = $this->resultCalculationMode($assessmentClass);
        foreach ($assessmentClass->assessmentSubjects as $assessmentSubject) {
            if (!$this->isSubjectApplicableToEnrollment($assessmentSubject, $studentEnrollment, $calculationMode)) {
                continue;
            }

            $mark = $assessmentSubject->marks()
                ->with('components.assessmentSubjectComponent')
                ->where('student_enrollment_id', $studentEnrollment->id)
                ->first();

            $termObtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
                ? (float) $mark->marks_obtained
                : null;
            $termGrade = $this->resolveTermGradeAndGpa($assessmentSubject, $termObtained);
            $classTestAverage = $this->classTestAverageForSubject($assessmentClass, (int) $assessmentSubject->subject_id, $studentEnrollment->id);
            $finalObtained = min((float) $assessmentSubject->total_marks, ((float) ($termObtained ?? 0)) + $classTestAverage);

            $subjectRows[] = [
                'subject_id' => (int) $assessmentSubject->subject_id,
                'subject' => $assessmentSubject->subject->name ?? "Subject #{$assessmentSubject->subject_id}",
                'total_marks' => $assessmentSubject->total_marks,
                'pass_marks' => $assessmentSubject->pass_marks,
                'term_obtained_marks' => $mark?->marks_obtained,
                'term_grade' => $termGrade['grade'],
                'term_gpa' => $termGrade['gpa'],
                'class_test_average' => $classTestAverage,
                'obtained_marks' => $finalObtained,
                'is_absent' => (bool) ($mark?->is_absent ?? false),
                'components' => $mark?->components ?? collect(),
                // If no component exists, treat the whole term mark as Written by default.
                'written_marks' => $this->resolveWrittenMarks(
                    $mark?->components ?? collect(),
                    $termObtained
                ),
                'mcq_marks' => $this->resolveComponentMarks($mark?->components ?? collect(), ['M', 'MCQ']),
                'practical_marks' => $this->resolveComponentMarks($mark?->components ?? collect(), ['P', 'PRACTICAL']),
                'is_pass' => $this->isSubjectPassForClassPdf(
                    [
                        'pass_marks' => (float) $assessmentSubject->pass_marks,
                        'component_columns' => $assessmentSubject->components->map(function ($component) {
                            return [
                                'id' => $component->id,
                                'pass_marks' => (float) ($component->pass_marks ?? 0),
                            ];
                        })->all(),
                    ],
                    $mark,
                    $mark?->components?->keyBy('assessment_subject_component_id') ?? collect(),
                    $finalObtained
                ),
            ];
        }

        return $subjectRows;
    }

    private function resolveTermGradeAndGpa($assessmentSubject, ?float $termObtained): array
    {
        if ($termObtained === null) {
            return ['grade' => null, 'gpa' => null];
        }

        $items = $assessmentSubject->gradingPolicy?->gradeScheme?->items
            ? $assessmentSubject->gradingPolicy->gradeScheme->items->sortBy('sort_order')->values()
            : collect();

        $matched = $items->first(function ($item) use ($termObtained) {
            return $termObtained >= (float) $item->min_mark && $termObtained <= (float) $item->max_mark;
        });

        if (!$matched) {
            return ['grade' => null, 'gpa' => null];
        }

        return [
            'grade' => (string) ($matched->letter_grade ?? ''),
            'gpa' => (float) ($matched->gpa ?? 0.0),
        ];
    }

    private function resolveWrittenMarks($components, ?float $termObtained): ?float
    {
        $written = $this->resolveComponentMarks($components, ['W', 'WRITTEN']);
        if ($written !== null) {
            return $written;
        }

        if (!$components || $components->isEmpty()) {
            return $termObtained;
        }

        return null;
    }

    private function isResultOperationAllowed(ExamAssessmentClass $examAssessmentClass): bool
    {
        $status = $examAssessmentClass->relationLoaded('examAssessment')
            ? $examAssessmentClass->examAssessment->status
            : $examAssessmentClass->examAssessment()->value('status');

        return $status === 'published';
    }

    private function resultCalculationMode(ExamAssessmentClass $assessmentClass): string
    {
        $mode = $assessmentClass->relationLoaded('examAssessment')
            ? $assessmentClass->examAssessment->result_calculation_mode
            : $assessmentClass->examAssessment()->value('result_calculation_mode');

        return $mode ?: 'standard_weighted';
    }

    private function isSubjectApplicableToEnrollment($assessmentSubject, StudentEnrollment $studentEnrollment, string $calculationMode): bool
    {
        if ($calculationMode !== 'ssc_optional_subject') {
            return true;
        }

        if (!(bool) ($assessmentSubject->is_fourth_subject_eligible ?? false)) {
            return true;
        }

        return (int) ($studentEnrollment->optional_subject_id ?? 0) === (int) $assessmentSubject->subject_id;
    }

    private function subjectCountsAsMandatory($assessmentSubject, StudentEnrollment $studentEnrollment, string $calculationMode): bool
    {
        if ((bool) ($assessmentSubject->exclude_from_final_gpa ?? $assessmentSubject->is_optional ?? false)) {
            return false;
        }

        if ($calculationMode !== 'ssc_optional_subject') {
            return true;
        }

        return !(
            (bool) ($assessmentSubject->is_fourth_subject_eligible ?? false)
            && (int) ($studentEnrollment->optional_subject_id ?? 0) === (int) $assessmentSubject->subject_id
        );
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

    private function resolveSubjectGpaForClassPdf(int $assessmentSubjectId, float $obtained, $gradeItemsByAssessmentSubject): float
    {
        $items = $gradeItemsByAssessmentSubject->get($assessmentSubjectId, collect());

        $matched = $items->first(function ($item) use ($obtained) {
            return $obtained >= (float) $item->min_mark && $obtained <= (float) $item->max_mark;
        });

        return $matched ? (float) $matched->gpa : 0.0;
    }

    private function isSubjectPassForClassPdf(array $layout, ?ExamMark $mark, $markComponents, float $finalObtained): bool
    {
        if ($finalObtained < (float) ($layout['pass_marks'] ?? 0)) {
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
                'assessment_subject' => $assessmentSubject,
                'assessment_subject_id' => $assessmentSubject->id,
                'subject_name' => $assessmentSubject->subject->name ?? ('Subject #' . $assessmentSubject->subject_id),
                'subject_id' => (int) $assessmentSubject->subject_id,
                'total_marks' => $assessmentSubject->total_marks,
                'pass_marks' => (float) $assessmentSubject->pass_marks,
                'component_columns' => $componentColumns,
                'show_total_column' => count($componentColumns) > 0,
                'show_average_column' => true,
            ];
        })->values();
    }

    private function buildClassPrintRows(
        ExamAssessmentClass $examAssessmentClass,
        $results,
        $subjectLayouts,
        $marksByKey,
        $gradeItemsByAssessmentSubject,
        array $classTestAverageMap,
        array $extraMarksByEnrollment
    )
    {
        return $results->map(function ($result) use (
            $examAssessmentClass,
            $subjectLayouts,
            $marksByKey,
            $gradeItemsByAssessmentSubject,
            $classTestAverageMap,
            $extraMarksByEnrollment
        ) {
            $subjectData = [];
            $hasFailedMandatorySubject = false;
            $calculationMode = $this->resultCalculationMode($examAssessmentClass);

            foreach ($subjectLayouts as $layout) {
                $assessmentSubject = $layout['assessment_subject'] ?? null;
                if ($assessmentSubject && !$this->isSubjectApplicableToEnrollment($assessmentSubject, $result->studentEnrollment, $calculationMode)) {
                    $subjectData[$layout['assessment_subject_id']] = [
                        'components' => array_fill(0, count($layout['component_columns']), null),
                        'total' => null,
                        'average' => null,
                        'gpa' => null,
                    ];
                    continue;
                }

                $mark = $marksByKey->get($layout['assessment_subject_id'] . ':' . $result->student_enrollment_id);
                $markComponents = $mark?->components?->keyBy('assessment_subject_component_id') ?? collect();

                $componentValues = [];
                foreach ($layout['component_columns'] as $componentColumn) {
                    $componentMark = $markComponents->get($componentColumn['id']);
                    $componentValues[] = ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null)
                        ? (float) $componentMark->marks_obtained
                        : null;
                }

                $termObtained = ($mark && !$mark->is_absent && $mark->marks_obtained !== null)
                    ? (float) $mark->marks_obtained
                    : null;
                $average = (float) ($classTestAverageMap[$layout['subject_id'] . ':' . $result->student_enrollment_id] ?? 0.0);
                $finalObtained = min(
                    (float) ($layout['total_marks'] ?? 0),
                    (float) (($termObtained ?? 0) + $average)
                );
                $subjectPass = $this->isSubjectPassForClassPdf($layout, $mark, $markComponents, $finalObtained);
                if (
                    !$subjectPass
                    && $assessmentSubject
                    && $this->subjectCountsAsMandatory($assessmentSubject, $result->studentEnrollment, $calculationMode)
                ) {
                    $hasFailedMandatorySubject = true;
                }

                $subjectData[$layout['assessment_subject_id']] = [
                    'components' => $componentValues,
                    'total' => $termObtained,
                    'average' => $average,
                    'gpa' => $subjectPass ? $this->resolveSubjectGpaForClassPdf(
                        $layout['assessment_subject_id'],
                        $finalObtained,
                        $gradeItemsByAssessmentSubject
                    ) : 0.0,
                ];
            }
            $extra = $extraMarksByEnrollment[(int) $result->student_enrollment_id] ?? [
                'homework_marks' => 0.0,
                'attendance_marks' => 0.0,
            ];
            $homeworkMarks = (float) ($extra['homework_marks'] ?? 0.0);
            $attendanceMarks = (float) ($extra['attendance_marks'] ?? 0.0);

            return [
                'roll' => $result->studentEnrollment->roll_number ?? '-',
                'student_name' => $result->studentEnrollment->student->name ?? ('Student #' . $result->student_enrollment_id),
                'subject_data' => $subjectData,
                'homework_marks' => $homeworkMarks,
                'attendance_marks' => $attendanceMarks,
                'total' => (float) $result->total_obtained + $homeworkMarks + $attendanceMarks,
                'gpa' => $hasFailedMandatorySubject ? 0.0 : (float) $result->gpa,
                'position' => $result->position ?? '-',
            ];
        })->values();
    }

    private function extraMarksByEnrollment(ExamAssessmentClass $assessmentClass, $enrollmentIds): array
    {
        if ($enrollmentIds->isEmpty()) {
            return [];
        }

        return StudentTermExtraMark::query()
            ->where('academic_year_id', $assessmentClass->examAssessment->academic_year_id)
            ->where('class_id', $assessmentClass->class_id)
            ->where('term_id', $assessmentClass->examAssessment->term_id)
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->get()
            ->mapWithKeys(function (StudentTermExtraMark $item) {
                return [
                    (int) $item->student_enrollment_id => [
                        'homework_marks' => (float) ($item->homework_marks ?? 0),
                        'attendance_marks' => (float) ($item->attendance_marks ?? 0),
                    ],
                ];
            })
            ->all();
    }

    private function buildClassTestAverageMapForAssessmentClass($examAssessmentClass, $assessmentSubjects, $enrollmentIds): array
    {
        $subjectIds = $assessmentSubjects->pluck('subject_id')->map(fn ($id) => (int) $id)->values();
        if ($subjectIds->isEmpty() || $enrollmentIds->isEmpty()) {
            return [];
        }

        $query = ClassTestMark::query()
            ->join('class_tests', 'class_tests.id', '=', 'class_test_marks.class_test_id')
            ->selectRaw('class_tests.subject_id as subject_id, class_test_marks.student_enrollment_id as student_enrollment_id, AVG(class_test_marks.marks_obtained) as average_marks')
            ->where('class_tests.academic_year_id', $examAssessmentClass->examAssessment->academic_year_id)
            ->where('class_tests.class_id', $examAssessmentClass->class_id)
            ->whereIn('class_tests.status', ['published', 'locked'])
            ->whereIn('class_tests.subject_id', $subjectIds)
            ->whereIn('class_test_marks.student_enrollment_id', $enrollmentIds)
            ->where('class_test_marks.is_absent', false)
            ->whereNotNull('class_test_marks.marks_obtained');

        if ($examAssessmentClass->examAssessment->term_id) {
            $query->where('class_tests.term_id', $examAssessmentClass->examAssessment->term_id);
        }

        return $query
            ->groupBy('class_tests.subject_id', 'class_test_marks.student_enrollment_id')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    ((int) $row->subject_id) . ':' . ((int) $row->student_enrollment_id) => round((float) $row->average_marks, 2),
                ];
            })
            ->all();
    }

    private function classTestAverageForSubject(ExamAssessmentClass $assessmentClass, int $subjectId, int $enrollmentId): float
    {
        $query = ClassTestMark::query()
            ->join('class_tests', 'class_tests.id', '=', 'class_test_marks.class_test_id')
            ->where('class_tests.academic_year_id', $assessmentClass->examAssessment->academic_year_id)
            ->where('class_tests.class_id', $assessmentClass->class_id)
            ->where('class_tests.subject_id', $subjectId)
            ->whereIn('class_tests.status', ['published', 'locked'])
            ->where('class_test_marks.student_enrollment_id', $enrollmentId)
            ->where('class_test_marks.is_absent', false)
            ->whereNotNull('class_test_marks.marks_obtained');

        if ($assessmentClass->examAssessment->term_id) {
            $query->where('class_tests.term_id', $assessmentClass->examAssessment->term_id);
        }

        return round((float) ($query->avg('class_test_marks.marks_obtained') ?? 0), 2);
    }

    private function draftClassTestsCountForAv(ExamAssessmentClass $assessmentClass): int
    {
        $subjectIds = $assessmentClass->assessmentSubjects
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($subjectIds->isEmpty()) {
            return 0;
        }

        $query = ClassTest::query()
            ->where('academic_year_id', $assessmentClass->examAssessment->academic_year_id)
            ->where('class_id', $assessmentClass->class_id)
            ->where('status', 'draft')
            ->whereIn('subject_id', $subjectIds);

        if ($assessmentClass->examAssessment->term_id) {
            $query->where('term_id', $assessmentClass->examAssessment->term_id);
        }

        return (int) $query->count();
    }

    private function isNurseryToClassTwo(ExamAssessmentClass $assessmentClass): bool
    {
        $classLevel = (int) optional($assessmentClass->schoolClass)->class_level;
        if ($classLevel <= 2) {
            return true;
        }

        $className = strtolower(trim((string) optional($assessmentClass->schoolClass)->name));
        return str_contains($className, 'nursery')
            || str_contains($className, 'nursary')
            || str_contains($className, 'class 1')
            || str_contains($className, 'class 2');
    }

    private function resolveComponentMarks($components, array $labels): ?float
    {
        if (!$components || $components->isEmpty()) {
            return null;
        }

        $wanted = array_map(fn ($value) => strtoupper(trim((string) $value)), $labels);

        foreach ($components as $componentMark) {
            if ($componentMark->is_absent || $componentMark->marks_obtained === null) {
                continue;
            }

            $component = $componentMark->assessmentSubjectComponent;
            $name = strtoupper(trim((string) optional($component)->component_name));
            $code = strtoupper(trim((string) optional($component)->component_code));
            if (in_array($name, $wanted, true) || in_array($code, $wanted, true)) {
                return (float) $componentMark->marks_obtained;
            }
        }

        return null;
    }

    private function highestFinalMarksBySubject(ExamAssessmentClass $assessmentClass): array
    {
        $assessmentClass->loadMissing(['examAssessment', 'assessmentSubjects']);
        $assessmentSubjects = $assessmentClass->assessmentSubjects;
        if ($assessmentSubjects->isEmpty()) {
            return [];
        }

        $enrollmentIds = StudentEnrollment::query()
            ->where('class_id', $assessmentClass->class_id)
            ->where('academic_year_id', $assessmentClass->examAssessment->academic_year_id)
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($enrollmentIds->isEmpty()) {
            return [];
        }

        $marksByKey = ExamMark::query()
            ->whereIn('assessment_subject_id', $assessmentSubjects->pluck('id'))
            ->whereIn('student_enrollment_id', $enrollmentIds)
            ->whereNotNull('marks_obtained')
            ->where('is_absent', false)
            ->get()
            ->keyBy(fn ($mark) => ((int) $mark->assessment_subject_id) . ':' . ((int) $mark->student_enrollment_id));

        $classTestAverageMap = $this->buildClassTestAverageMapForAssessmentClass(
            $assessmentClass,
            $assessmentSubjects,
            $enrollmentIds
        );

        $highestBySubject = [];
        foreach ($assessmentSubjects as $assessmentSubject) {
            $highest = 0.0;
            foreach ($enrollmentIds as $enrollmentId) {
                $term = (float) optional($marksByKey->get($assessmentSubject->id . ':' . $enrollmentId))->marks_obtained;
                $average = (float) ($classTestAverageMap[((int) $assessmentSubject->subject_id) . ':' . ((int) $enrollmentId)] ?? 0.0);
                $final = min((float) $assessmentSubject->total_marks, $term + $average);
                if ($final > $highest) {
                    $highest = $final;
                }
            }

            $highestBySubject[(int) $assessmentSubject->subject_id] = round($highest, 2);
        }

        return $highestBySubject;
    }
}
