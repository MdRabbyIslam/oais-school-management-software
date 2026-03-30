<?php

namespace App\Services\Exam;

use App\Models\ClassTestMark;
use App\Models\ExamAssessmentClass;
use App\Models\ExamMark;
use App\Models\ExamStudentResult;
use App\Models\StudentEnrollment;
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
            $calculationMode = (string) ($assessmentClass->examAssessment->result_calculation_mode ?? 'standard_weighted');

            $processed = 0;
            $skipped = 0;

            foreach ($enrollments as $enrollment) {
                $weightedObtained = 0.0;
                $weightedTotal = 0.0;
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
                    $totalMarks = (float) $subjectSetup->total_marks;
                    $passMarks = (float) $subjectSetup->pass_marks;

                    $markKey = $subjectSetup->id . ':' . $enrollment->id;
                    $mark = $markMap->get($markKey);

                    $examObtained = 0.0;
                    if ($mark && !$mark->is_absent && $mark->marks_obtained !== null) {
                        $examObtained = (float) $mark->marks_obtained;
                    }

                    $classTestAverage = (float) ($classTestAverageMap[$subjectSetup->subject_id . ':' . $enrollment->id] ?? 0.0);
                    $obtained = min($totalMarks, $examObtained + $classTestAverage);

                    $gradeRow = $this->resolveGrade(
                        $obtained,
                        $subjectSetup->gradingPolicy?->gradeScheme?->items ?? collect()
                    );
                    $subjectGpa = (float) ($gradeRow['gpa'] ?? 0);
                    $subjectPassed = $this->isSubjectPass($subjectSetup, $mark, $obtained, $passMarks);

                    $weightedObtained += $obtained * $weight;
                    $weightedTotal += $totalMarks * $weight;

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

                if ($weightedTotal <= 0) {
                    $skipped++;
                    continue;
                }

                $percentage = ($weightedObtained / $weightedTotal) * 100;
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
                        'total_obtained' => round($weightedObtained, 2),
                        'total_marks' => round($weightedTotal, 2),
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

            $this->applyPosition($assessmentClass->id);

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
        $matched = $schemeItems
            ->sortBy('sort_order')
            ->first(function ($item) use ($obtained) {
                return $obtained >= (float) $item->min_mark && $obtained <= (float) $item->max_mark;
            });

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

    private function applyPosition(int $assessmentClassId): void
    {
        $results = ExamStudentResult::query()
            ->where('assessment_class_id', $assessmentClassId)
            ->orderByDesc('percentage')
            ->orderByDesc('total_obtained')
            ->get();

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
}
