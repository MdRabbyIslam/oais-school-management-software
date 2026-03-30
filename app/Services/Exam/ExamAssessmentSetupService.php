<?php

namespace App\Services\Exam;

use App\Models\ExamAssessmentClass;
use App\Models\ExamAssessmentSubject;
use App\Models\GradingPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAssessmentSetupService
{
    /**
     * Sync class-subject configuration from active grading policies.
     *
     * @return array{synced_subjects:int, skipped_subjects_without_policy:int, component_sync_locked_by_marks:int}
     */
    public function syncFromPolicies(ExamAssessmentClass $assessmentClass): array
    {
        return DB::transaction(function () use ($assessmentClass) {
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

            $syncedSubjects = 0;
            $skippedSubjectsWithoutPolicy = 0;
            $componentSyncLockedByMarks = 0;

            foreach ($subjectIds as $subjectId) {
                $policy = $policiesBySubject->get($subjectId);
                if (!$policy) {
                    $skippedSubjectsWithoutPolicy++;
                    continue;
                }

                $subject = ExamAssessmentSubject::updateOrCreate(
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
                        'weight' => $policy->weight ?? 1,
                    ]
                );

                // Keep component IDs stable once marks exist; recreating components
                // breaks linkage with exam_mark_components.
                if (!$subject->marks()->exists()) {
                    $this->syncSubjectComponentsFromPolicy($subject, $policy);
                } else {
                    $componentSyncLockedByMarks++;
                }

                $syncedSubjects++;
            }

            return [
                'synced_subjects' => $syncedSubjects,
                'skipped_subjects_without_policy' => $skippedSubjectsWithoutPolicy,
                'component_sync_locked_by_marks' => $componentSyncLockedByMarks,
            ];
        });
    }

    public function upsertSubject(ExamAssessmentClass $assessmentClass, array $data): ExamAssessmentSubject
    {
        return DB::transaction(function () use ($assessmentClass, $data) {
            $subjectId = (int) $data['subject_id'];
            $policy = GradingPolicy::query()
                ->where('class_id', $assessmentClass->class_id)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->with('components')
                ->first();

            if (!$policy) {
                throw ValidationException::withMessages([
                    'subject_id' => 'No active grading policy found for this class and subject. Please create a grading policy first.',
                ]);
            }

            $subject = ExamAssessmentSubject::updateOrCreate(
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
                    'weight' => $policy->weight ?? 1,
                ]
            );

            if ($subject->marks()->exists()) {
                throw ValidationException::withMessages([
                    'subject_id' => 'Marks already exist for this subject in this assessment. Component structure cannot be changed now.',
                ]);
            }

            $this->syncSubjectComponentsFromPolicy($subject, $policy);

            return $subject->refresh();
        });
    }

    private function syncSubjectComponentsFromPolicy(ExamAssessmentSubject $subject, GradingPolicy $policy): void
    {
        $subject->components()->delete();
        foreach ($policy->components as $component) {
            $subject->components()->create([
                'component_name' => $component['component_name'],
                'component_code' => $component['component_code'],
                'total_marks' => $component['total_marks'],
                'pass_marks' => $component['pass_marks'] ?? null,
                'sort_order' => $component['sort_order'] ?? 1,
            ]);
        }
    }
}
