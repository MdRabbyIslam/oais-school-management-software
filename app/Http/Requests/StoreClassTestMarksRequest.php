<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassTestMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array'],
            'rows.*.student_enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'rows.*.marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'rows.*.is_absent' => ['nullable', 'boolean'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}

