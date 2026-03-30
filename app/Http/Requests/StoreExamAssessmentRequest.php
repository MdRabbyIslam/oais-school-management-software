<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreExamAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['nullable', 'exists:terms,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,published,locked'],
            'result_calculation_mode' => ['required', 'in:standard_weighted,ssc_optional_subject'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $academicYearId = (int) $this->input('academic_year_id');
            $termId = (int) $this->input('term_id');

            if ($termId > 0 && $academicYearId > 0) {
                $termMatchesYear = DB::table('terms')
                    ->where('id', $termId)
                    ->where('academic_year_id', $academicYearId)
                    ->exists();

                if (!$termMatchesYear) {
                    $validator->errors()->add('term_id', 'Selected term does not belong to the selected academic year.');
                }
            }
        });
    }
}
