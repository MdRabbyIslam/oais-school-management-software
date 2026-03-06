<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamAssessmentSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'is_optional' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0.01'],
            'components' => ['nullable', 'array'],
            'components.*.component_name' => ['nullable', 'string', 'max:100'],
            'components.*.component_code' => ['nullable', 'string', 'max:30'],
            'components.*.total_marks' => ['nullable', 'numeric', 'min:0'],
            'components.*.pass_marks' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
