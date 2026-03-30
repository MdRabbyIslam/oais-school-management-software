<?php

namespace App\Services\Exam;

use App\Models\ExamAssessment;
use App\Models\ExamAssessmentClass;
use App\Models\ExamAssessmentSubject;
use App\Models\GradingPolicy;
use Illuminate\Support\Facades\DB;

class ExamAssessmentService
{
    /**
     * @return array{assessment: ExamAssessment, initialized_subjects:int, skipped_subjects_without_policy:int, processed_classes:int}
     */
    public function create(array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $classIds = collect($data['class_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            unset($data['class_ids']);

            $assessment = ExamAssessment::create(array_merge($data, [
                'created_by' => $createdBy,
            ]));

            $initializedSubjects = 0;
            $skippedSubjectsWithoutPolicy = 0;

            foreach ($classIds as $classId) {
                $assessmentClass = $assessment->assessmentClasses()->firstOrCreate([
                    'class_id' => $classId,
                ]);

                $summary = $this->initializeSubjectsFromClassDefaults($assessmentClass);
                $initializedSubjects += $summary['initialized_subjects'];
                $skippedSubjectsWithoutPolicy += $summary['skipped_subjects_without_policy'];
            }

            return [
                'assessment' => $assessment,
                'initialized_subjects' => $initializedSubjects,
                'skipped_subjects_without_policy' => $skippedSubjectsWithoutPolicy,
                'processed_classes' => $classIds->count(),
            ];
        });
    }

    /**
     * @return array{assessment: ExamAssessment, initialized_subjects:int, skipped_subjects_without_policy:int, processed_classes:int}
     */
    public function update(ExamAssessment $assessment, array $data): array
    {
        return DB::transaction(function () use ($assessment, $data) {
            $classIds = collect($data['class_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            unset($data['class_ids']);

            $assessment->update($data);

            // Safe sync: add missing classes only to avoid accidental data loss.
            $existingClassIds = $assessment->assessmentClasses()->pluck('class_id')->all();
            $newClassIds = $classIds->diff($existingClassIds);
            $initializedSubjects = 0;
            $skippedSubjectsWithoutPolicy = 0;

            foreach ($newClassIds as $classId) {
                $assessmentClass = $assessment->assessmentClasses()->create([
                    'class_id' => $classId,
                ]);
                $summary = $this->initializeSubjectsFromClassDefaults($assessmentClass);
                $initializedSubjects += $summary['initialized_subjects'];
                $skippedSubjectsWithoutPolicy += $summary['skipped_subjects_without_policy'];
            }

            return [
                'assessment' => $assessment->refresh(),
                'initialized_subjects' => $initializedSubjects,
                'skipped_subjects_without_policy' => $skippedSubjectsWithoutPolicy,
                'processed_classes' => $newClassIds->count(),
            ];
        });
    }

    /**
     * @return array{initialized_subjects:int, skipped_subjects_without_policy:int}
     */
    private function initializeSubjectsFromClassDefaults(ExamAssessmentClass $assessmentClass): array
    {
        $subjectIds = DB::table('class_subject')
            ->where('class_id', $assessmentClass->class_id)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $policiesBySubject = GradingPolicy::query()
            ->where('class_id', $assessmentClass->class_id)
            ->where('is_active', true)
            ->with('components')
            ->get()
            ->keyBy('subject_id');

        $initializedSubjects = 0;
        $skippedSubjectsWithoutPolicy = 0;

        foreach ($subjectIds as $subjectId) {
            $policy = $policiesBySubject->get($subjectId);
            if (!$policy) {
                $skippedSubjectsWithoutPolicy++;
                continue;
            }

            $subject = ExamAssessmentSubject::firstOrCreate(
                [
                    'assessment_class_id' => $assessmentClass->id,
                    'subject_id' => $subjectId,
                ],
                [
                    'grading_policy_id' => $policy->id,
                    'total_marks' => $policy->total_marks,
                    'pass_marks' => $policy->pass_marks,
                    'exclude_from_final_gpa' => (bool) $policy->exclude_from_final_gpa,
                    'is_fourth_subject_eligible' => (bool) $policy->is_fourth_subject_eligible,
                    'is_optional' => (bool) $policy->is_optional,
                    'weight' => $policy->weight ?? 1.00,
                ]
            );

            if ($subject->wasRecentlyCreated) {
                foreach ($policy->components as $component) {
                    $subject->components()->create([
                        'component_name' => $component->component_name,
                        'component_code' => $component->component_code,
                        'total_marks' => $component->total_marks,
                        'pass_marks' => $component->pass_marks,
                        'sort_order' => $component->sort_order ?? 1,
                    ]);
                }
                $initializedSubjects++;
            }
        }

        return [
            'initialized_subjects' => $initializedSubjects,
            'skipped_subjects_without_policy' => $skippedSubjectsWithoutPolicy,
        ];
    }
}
