<?php

namespace App\Http\Requests;

use App\Models\GradingPolicy;
use Illuminate\Validation\Validator;

class UpdateGradingPolicyRequest extends StoreGradingPolicyRequest
{
    public function withValidator(Validator $validator): void
    {
        $gradingPolicy = $this->route('grading_policy');

        $validator->after(function (Validator $validator) use ($gradingPolicy) {
            $classId = $this->input('class_id');
            $subjectId = $this->input('subject_id');

            if (!$classId || !$subjectId) {
                return;
            }

            $exists = GradingPolicy::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('id', '!=', $gradingPolicy?->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('subject_id', 'A grading policy already exists for this class and subject. Edit that one instead.');
            }
        });
    }
}
