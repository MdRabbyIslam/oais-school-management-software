<?php

namespace App\Http\Requests;

use App\Models\GradingPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGradingPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'total_marks' => ['required', 'numeric', 'min:1'],
            'pass_marks' => ['required', 'numeric', 'min:0', 'lte:total_marks'],
            'grade_scheme_id' => ['required', 'exists:grade_schemes,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $classId = $this->input('class_id');
            $subjectId = $this->input('subject_id');

            if (!$classId || !$subjectId) {
                return;
            }

            $exists = GradingPolicy::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->exists();

            if ($exists) {
                $validator->errors()->add('subject_id', 'A grading policy already exists for this class and subject. Edit the existing one instead.');
            }
        });
    }
}
