<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreClassTestRequest extends FormRequest
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
            'create_for_all_subjects' => ['nullable', 'boolean'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'name' => ['required', 'string', 'max:150'],
            'test_date' => ['nullable', 'date'],
            'total_marks' => ['required', 'numeric', 'min:1'],
            'pass_marks' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,published,locked'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $academicYearId = (int) $this->input('academic_year_id');
            $termId = (int) $this->input('term_id');
            $classId = (int) $this->input('class_id');
            $subjectId = (int) $this->input('subject_id');
            $createForAllSubjects = $this->boolean('create_for_all_subjects');
            $totalMarks = $this->input('total_marks');
            $passMarks = $this->input('pass_marks');

            if ($passMarks !== null && $totalMarks !== null && (float) $passMarks > (float) $totalMarks) {
                $validator->errors()->add('pass_marks', 'Pass marks cannot be greater than total marks.');
            }

            if ($termId > 0 && $academicYearId > 0) {
                $termMatchesYear = DB::table('terms')
                    ->where('id', $termId)
                    ->where('academic_year_id', $academicYearId)
                    ->exists();

                if (!$termMatchesYear) {
                    $validator->errors()->add('term_id', 'Selected term does not belong to the selected academic year.');
                }
            }

            if ($classId > 0 && $createForAllSubjects) {
                $hasAssignedSubjects = DB::table('class_subject')
                    ->where('class_id', $classId)
                    ->exists();

                if (!$hasAssignedSubjects) {
                    $validator->errors()->add('class_id', 'Selected class has no assigned subjects.');
                }
            }

            if (!$createForAllSubjects) {
                if ($subjectId <= 0) {
                    $validator->errors()->add('subject_id', 'Subject field is required.');
                    return;
                }

                if ($classId > 0) {
                    $subjectAssigned = DB::table('class_subject')
                        ->where('class_id', $classId)
                        ->where('subject_id', $subjectId)
                        ->exists();

                    if (!$subjectAssigned) {
                        $validator->errors()->add('subject_id', 'Selected subject is not assigned to the selected class.');
                    }
                }
            }
        });
    }
}
