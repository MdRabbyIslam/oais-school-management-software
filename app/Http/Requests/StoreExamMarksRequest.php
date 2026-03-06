<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'assessment_subject_id' => ['required', 'exists:exam_assessment_subjects,id'],
            'rows' => ['required', 'array'],
            'rows.*.student_enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'rows.*.marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'rows.*.is_absent' => ['nullable', 'boolean'],
            'rows.*.component_marks' => ['nullable', 'array'],
            'rows.*.component_marks.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

