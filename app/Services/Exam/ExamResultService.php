<?php

namespace App\Services\Exam;

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
                ->whereIn('assessment_subject_id', $subjects->pluck('id'))
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->get()
                ->keyBy(fn ($mark) => $mark->assessment_subject_id . ':' . $mark->student_enrollment_id);

            $processed = 0;
            $skipped = 0;

            foreach ($enrollments as $enrollment) {
                $weightedObtained = 0.0;
                $weightedTotal = 0.0;
                $weightedGpaPoints = 0.0;
                $weightedGpaBase = 0.0;
                $failedMandatory = 0;

                foreach ($subjects as $subjectSetup) {
                    $weight = (float) $subjectSetup->weight;
                    $totalMarks = (float) $subjectSetup->total_marks;
                    $passMarks = (float) $subjectSetup->pass_marks;
                    $isOptional = (bool) $subjectSetup->is_optional;

                    $markKey = $subjectSetup->id . ':' . $enrollment->id;
                    $mark = $markMap->get($markKey);

                    $obtained = 0.0;
                    if ($mark && !$mark->is_absent && $mark->marks_obtained !== null) {
                        $obtained = (float) $mark->marks_obtained;
                    }

                    $gradeRow = $this->resolveGrade(
                        $obtained,
                        $subjectSetup->gradingPolicy?->gradeScheme?->items ?? collect()
                    );
                    $subjectGpa = (float) ($gradeRow['gpa'] ?? 0);
                    $subjectPassed = $obtained >= $passMarks;

                    $weightedObtained += $obtained * $weight;
                    $weightedTotal += $totalMarks * $weight;

                    if (!$isOptional) {
                        $weightedGpaPoints += $subjectGpa * $weight;
                        $weightedGpaBase += $weight;

                        if (!$subjectPassed) {
                            $failedMandatory++;
                        }
                    }
                }

                if ($weightedTotal <= 0) {
                    $skipped++;
                    continue;
                }

                $percentage = ($weightedObtained / $weightedTotal) * 100;
                $isPass = $failedMandatory === 0;
                $gpa = $isPass && $weightedGpaBase > 0 ? ($weightedGpaPoints / $weightedGpaBase) : 0;
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
}
