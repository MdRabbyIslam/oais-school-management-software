<?php

namespace App\Services\Exam;

use App\Models\ClassTestMark;
use App\Models\ExamAssessmentClass;
use App\Models\ExamMark;
use App\Models\ExamStudentResult;
use App\Models\StudentEnrollment;
use App\Models\StudentTermExtraMark;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamResultService
{
    /**
     * Calculate and upsert result summaries for one assessment class.
     *
     * @return array{processed:int, skipped:int}
     */
    public function calculateForClass(ExamAssessmentClass $assessmentClass): array
    {
        return DB::transaction(function () use ($assessmentClass) {
            $assessmentClass->load([
                'examAssessment',
                'assessmentSubjects.gradingPolicy.gradeScheme.items',
                'assessmentSubjects.subject',
                'assessmentSubjects.components',
            ]);

            $subjects = $assessmentClass->assessmentSubjects;
            if ($subjects->isEmpty()) {
                throw ValidationException::withMessages([
                    'results' => 'No subject setup found. Configure exam subjects before publishing results.',
                ]);
            }

            $enrollments = StudentEnrollment::query()
                ->where('class_id', $assessmentClass->class_id)
                ->where('academic_year_id', $assessmentClass->examAssessment->academic_year_id)
                ->where('status', 'active')
                ->get();

            $markMap = ExamMark::query()
                ->with('components')
                ->whereIn('assessment_subject_id', $subjects->pluck('id'))
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->get()
                ->keyBy(fn ($mark) => $mark->assessment_subject_id . ':' . $mark->student_enrollment_id);

            $classTestAverageMap = $this->buildClassTestAverageMap($assessmentClass, $subjects, $enrollments);
            $extraMarksByEnrollment = $this->extraMarksByEnrollment($assessmentClass, $enrollments);
            $calculationMode = (string) ($assessmentClass->examAssessment->result_calculation_mode ?? 'standard_weighted');

            $processed = 0;
            $skipped = 0;

            foreach ($enrollments as $enrollment) {
                $totalObtained = 0.0;
                $totalMarks = 0.0;
                $percentageObtained = 0.0;
                $weightedGpaPoints = 0.0;
                $weightedGpaBase = 0.0;
                $failedMandatory = 0;
                $optionalBonusPoints = 0.0;

                foreach ($subjects as $subjectSetup) {
                    $role = $this->resolveSubjectRole($subjectSetup, $enrollment, $calculationMode);
                    if (!$role['counts_in_totals']) {
                        continue;
                    }

                    $weight = (float) $subjectSetup->weight;
                    $subjectTotalMarks = (float) $subjectSetup->total_marks;
                    $passMarks = (float) $subjectSetup->pass_marks;

                    $markKey = $subjectSetup->id . ':' . $enrollment->id;
                    $mark = $markMap->get($markKey);

                    $examObtained = 0.0;
                    if ($mark && !$mark->is_absent && $mark->marks_obtained !== null) {
                        $examObtained = (float) $mark->marks_obtained;
                    }

                    $classTestAverage = (float) ($classTestAverageMap[$subjectSetup->subject_id . ':' . $enrollment->id] ?? 0.0);
                    $obtained = $examObtained + $classTestAverage;

                    $gradeRow = $this->resolveGrade(
                        $examObtained,
                        $subjectSetup->gradingPolicy?->gradeScheme?->items ?? collect()
                    );
                    $subjectGpa = (float) ($gradeRow['gpa'] ?? 0);
                    $subjectPassed = $this->isSubjectPass($subjectSetup, $mark, $obtained, $passMarks);

                    $totalObtained += $obtained;
                    $percentageObtained += $examObtained;
                    $totalMarks += $subjectTotalMarks;
                    if ($role['counts_in_gpa']) {
                        if ($calculationMode === 'ssc_optional_subject') {
                            $weightedGpaPoints += $subjectGpa;
                            $weightedGpaBase += 1;
                        } else {
                            $weightedGpaPoints += $subjectGpa * $weight;
                            $weightedGpaBase += $weight;
                        }

                        if ($role['counts_in_pass_fail'] && !$subjectPassed) {
                            $failedMandatory++;
                        }
                    }

                    if ($role['counts_as_optional_bonus']) {
                        $optionalBonusPoints += max(0, $subjectGpa - 2.0);
                    }
                }

                if ($totalMarks <= 0) {
                    $skipped++;
                    continue;
                }

                $percentage = ($percentageObtained / $totalMarks) * 100;
                $isPass = $failedMandatory === 0;
                $gpa = 0.0;
                if ($isPass && $weightedGpaBase > 0) {
                    $gpa = $weightedGpaPoints / $weightedGpaBase;
                    if ($calculationMode === 'ssc_optional_subject') {
                        $gpa += $optionalBonusPoints / $weightedGpaBase;
                    }
                }
                $finalGrade = $isPass ? $this->gradeFromGpa($gpa) : 'F';

                ExamStudentResult::updateOrCreate(
                    [
                        'assessment_class_id' => $assessmentClass->id,
                        'student_enrollment_id' => $enrollment->id,
                    ],
                    [
                        'total_obtained' => round($totalObtained, 2),
                        'total_marks' => round($totalMarks, 2),
                        'percentage' => round($percentage, 2),
                        'gpa' => round($gpa, 2),
                        'final_grade' => $finalGrade,
                        'failed_subject_count' => $failedMandatory,
                        'is_pass' => $isPass,
                        'calculated_at' => now(),
                    ]
                );

                $processed++;
            }

            $this->applyPosition($assessmentClass, $extraMarksByEnrollment);

            return [
                'processed' => $processed,
                'skipped' => $skipped,
            ];
        });
    }

    public function publish(ExamAssessmentClass $assessmentClass, int $publishedBy): array
    {
        $summary = $this->calculateForClass($assessmentClass);

        $assessmentClass->update([
            'is_published' => true,
            'published_by' => $publishedBy,
            'published_at' => now(),
        ]);

        return $summary;
    }

    private function buildClassTestAverageMap(ExamAssessmentClass $assessmentClass, $subjects, $enrollments): array
    {
        $subjectIds = $subjects->pluck('subject_id')->map(fn ($id) => (int) $id)->values();
        $enrollmentIds = $enrollments->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($subjectIds->isEmpty() || $enrollmentIds->isEmpty()) {
            return [];
        }

        $query = ClassTestMark::query()
            ->join('class_tests', 'class_tests.id', '=', 'class_test_marks.class_test_id')
            ->selectRaw('class_tests.subject_id as subject_id, class_test_marks.student_enrollment_id as student_enrollment_id, AVG(class_test_marks.marks_obtained) as average_marks')
            ->where('class_tests.academic_year_id', $assessmentClass->examAssessment->academic_year_id)
            ->where('class_tests.class_id', $assessmentClass->class_id)
            ->whereIn('class_tests.status', ['published', 'locked'])
            ->whereIn('class_tests.subject_id', $subjectIds)
            ->whereIn('class_test_marks.student_enrollment_id', $enrollmentIds)
            ->where('class_test_marks.is_absent', false)
            ->whereNotNull('class_test_marks.marks_obtained');

        if ($assessmentClass->examAssessment->term_id) {
            $query->where('class_tests.term_id', $assessmentClass->examAssessment->term_id);
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

    private function resolveGrade(float $obtained, $schemeItems): array
    {
        $sortedItems = $schemeItems
            ->sortBy('sort_order')
            ->values();

        $matched = $sortedItems->first(function ($item) use ($obtained) {
            return $obtained >= (float) $item->min_mark && $obtained <= (float) $item->max_mark;
        });

        if (!$matched && $sortedItems->isNotEmpty() && $obtained > (float) ($sortedItems->last()->max_mark ?? 0)) {
            $matched = $sortedItems->last();
        }

        if (!$matched) {
            return ['letter' => 'F', 'gpa' => 0.0];
        }

        return [
            'letter' => $matched->letter_grade,
            'gpa' => (float) $matched->gpa,
        ];
    }

    private function gradeFromGpa(float $gpa): string
    {
        if ($gpa >= 5.00) {
            return 'A+';
        }
        if ($gpa >= 4.00) {
            return 'A';
        }
        if ($gpa >= 3.50) {
            return 'A-';
        }
        if ($gpa >= 3.00) {
            return 'B';
        }
        if ($gpa >= 2.00) {
            return 'C';
        }

        return 'F';
    }

    private function applyPosition(ExamAssessmentClass $assessmentClass, array $extraMarksByEnrollment): void
    {
        $results = ExamStudentResult::query()
            ->select('exam_student_results.*')
            ->with('studentEnrollment')
            ->where('assessment_class_id', $assessmentClass->id)
            ->get()
            ->sort(function (ExamStudentResult $left, ExamStudentResult $right) use ($extraMarksByEnrollment) {
                $leftExtra = $extraMarksByEnrollment[(int) $left->student_enrollment_id] ?? [
                    'homework_marks' => 0.0,
                    'attendance_marks' => 0.0,
                ];
                $rightExtra = $extraMarksByEnrollment[(int) $right->student_enrollment_id] ?? [
                    'homework_marks' => 0.0,
                    'attendance_marks' => 0.0,
                ];

                $leftGrandTotal = (float) $left->total_obtained
                    + (float) ($leftExtra['homework_marks'] ?? 0.0)
                    + (float) ($leftExtra['attendance_marks'] ?? 0.0);
                $rightGrandTotal = (float) $right->total_obtained
                    + (float) ($rightExtra['homework_marks'] ?? 0.0)
                    + (float) ($rightExtra['attendance_marks'] ?? 0.0);

                if ($leftGrandTotal !== $rightGrandTotal) {
                    return $rightGrandTotal <=> $leftGrandTotal;
                }

                if ((float) $left->gpa !== (float) $right->gpa) {
                    return (float) $right->gpa <=> (float) $left->gpa;
                }

                if ((float) $left->total_obtained !== (float) $right->total_obtained) {
                    return (float) $right->total_obtained <=> (float) $left->total_obtained;
                }

                $leftRoll = $left->studentEnrollment?->roll_number;
                $rightRoll = $right->studentEnrollment?->roll_number;
                if ($leftRoll === null && $rightRoll !== null) {
                    return 1;
                }
                if ($leftRoll !== null && $rightRoll === null) {
                    return -1;
                }

                return ((int) ($leftRoll ?? 0)) <=> ((int) ($rightRoll ?? 0));
            })
            ->values();

        $position = 0;
        foreach ($results as $result) {
            $position++;
            $result->update(['position' => $position]);
        }
    }

    private function isSubjectPass($subjectSetup, ?ExamMark $mark, float $obtained, float $passMarks): bool
    {
        if ($obtained < $passMarks) {
            return false;
        }

        if ($subjectSetup->components->isEmpty()) {
            return true;
        }

        $componentMarks = $mark?->components?->keyBy('assessment_subject_component_id') ?? collect();
        foreach ($subjectSetup->components as $componentSetup) {
            $passMark = (float) ($componentSetup->pass_marks ?? 0);
            $componentMark = $componentMarks->get($componentSetup->id);

            $obtainedComponent = null;
            if ($componentMark && !$componentMark->is_absent && $componentMark->marks_obtained !== null) {
                $obtainedComponent = (float) $componentMark->marks_obtained;
            }

            if ($obtainedComponent === null || $obtainedComponent < $passMark) {
                return false;
            }
        }

        return true;
    }

    private function resolveSubjectRole($subjectSetup, StudentEnrollment $enrollment, string $calculationMode): array
    {
        $excludeFromFinalGpa = (bool) ($subjectSetup->exclude_from_final_gpa ?? $subjectSetup->is_optional ?? false);
        $isFourthSubjectEligible = (bool) ($subjectSetup->is_fourth_subject_eligible ?? false);
        $isSelectedFourthSubject = $calculationMode === 'ssc_optional_subject'
            && $isFourthSubjectEligible
            && (int) ($enrollment->optional_subject_id ?? 0) === (int) $subjectSetup->subject_id;

        if ($calculationMode === 'ssc_optional_subject') {
            if ($isSelectedFourthSubject) {
                return [
                    'counts_in_totals' => true,
                    'counts_in_gpa' => false,
                    'counts_in_pass_fail' => false,
                    'counts_as_optional_bonus' => true,
                ];
            }

            if ($isFourthSubjectEligible) {
                return [
                    'counts_in_totals' => false,
                    'counts_in_gpa' => false,
                    'counts_in_pass_fail' => false,
                    'counts_as_optional_bonus' => false,
                ];
            }
        }

        if ($excludeFromFinalGpa) {
            return [
                'counts_in_totals' => true,
                'counts_in_gpa' => false,
                'counts_in_pass_fail' => false,
                'counts_as_optional_bonus' => false,
            ];
        }

        return [
            'counts_in_totals' => true,
            'counts_in_gpa' => true,
            'counts_in_pass_fail' => true,
            'counts_as_optional_bonus' => false,
        ];
    }

    private function extraMarksByEnrollment(ExamAssessmentClass $assessmentClass, $enrollments): array
    {
        $enrollmentIds = $enrollments->pluck('id')->map(fn ($id) => (int) $id)->values();
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
}

