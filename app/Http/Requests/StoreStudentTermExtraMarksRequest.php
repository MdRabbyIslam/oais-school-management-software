<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreStudentTermExtraMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.student_enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'rows.*.homework_marks' => ['nullable', 'numeric', 'min:0'],
            'rows.*.attendance_marks' => ['nullable', 'numeric', 'min:0'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $academicYearId = (int) $this->input('academic_year_id');
            $termId = (int) $this->input('term_id');
            $classId = (int) $this->input('class_id');

            if ($termId > 0 && $academicYearId > 0) {
                $termMatchesYear = DB::table('terms')
                    ->where('id', $termId)
                    ->where('academic_year_id', $academicYearId)
                    ->exists();

                if (!$termMatchesYear) {
                    $validator->errors()->add('term_id', 'Selected term does not belong to selected academic year.');
                }
            }

            $enrollmentIds = collect($this->input('rows', []))
                ->pluck('student_enrollment_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($enrollmentIds->isEmpty()) {
                return;
            }

            $validCount = DB::table('student_enrollments')
                ->whereIn('id', $enrollmentIds)
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->where('status', 'active')
                ->count();

            if ($validCount !== $enrollmentIds->count()) {
                $validator->errors()->add('rows', 'One or more students do not belong to the selected academic year/class.');
            }
        });
    }
}

