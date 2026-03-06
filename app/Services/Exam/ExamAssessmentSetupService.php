<?php

namespace App\Services\Exam;

use App\Models\ExamAssessmentClass;
use App\Models\ExamAssessmentSubject;
use App\Models\GradingPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamAssessmentSetupService
{
    public function upsertSubject(ExamAssessmentClass $assessmentClass, array $data): ExamAssessmentSubject
    {
        return DB::transaction(function () use ($assessmentClass, $data) {
            $subjectId = (int) $data['subject_id'];
            $policy = GradingPolicy::query()
                ->where('class_id', $assessmentClass->class_id)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->first();

            if (!$policy) {
                throw ValidationException::withMessages([
                    'subject_id' => 'No active grading policy found for this class and subject. Please create a grading policy first.',
                ]);
            }

            $components = collect($data['components'] ?? [])->filter(function ($component) {
                return !empty($component['component_name']) && !empty($component['component_code']);
            })->values();

            $totalMarks = (float) $policy->total_marks;
            $componentTotal = (float) $components->sum(fn ($c) => (float) ($c['total_marks'] ?? 0));

            if ($components->isNotEmpty() && abs($componentTotal - $totalMarks) > 0.00001) {
                throw ValidationException::withMessages([
                    'components' => 'Sum of component total marks must be equal to subject total marks.',
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
                    'is_optional' => (bool) ($data['is_optional'] ?? false),
                    'weight' => $data['weight'] ?? 1,
                ]
            );

            $subject->components()->delete();
            foreach ($components as $index => $component) {
                $subject->components()->create([
                    'component_name' => $component['component_name'],
                    'component_code' => $component['component_code'],
                    'total_marks' => $component['total_marks'],
                    'pass_marks' => $component['pass_marks'] ?? null,
                    'sort_order' => $index + 1,
                ]);
            }

            return $subject->refresh();
        });
    }
}
