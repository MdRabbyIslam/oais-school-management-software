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
            'exclude_from_final_gpa' => ['nullable', 'boolean'],
            'is_fourth_subject_eligible' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0.01'],
            'is_active' => ['nullable', 'boolean'],
            'components' => ['nullable', 'array'],
            'components.*.component_name' => ['nullable', 'string', 'max:100'],
            'components.*.component_code' => ['nullable', 'string', 'max:30'],
            'components.*.total_marks' => ['nullable', 'numeric', 'min:0'],
            'components.*.pass_marks' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $classId = $this->input('class_id');
            $subjectId = $this->input('subject_id');
            $currentPolicy = $this->route('grading_policy') ?? $this->route('gradingPolicy');

            if (!$classId || !$subjectId) {
                return;
            }

            $existsQuery = GradingPolicy::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId);

            if ($currentPolicy) {
                $existsQuery->where('id', '!=', $currentPolicy->id);
            }

            $exists = $existsQuery->exists();

            if ($exists) {
                $validator->errors()->add('subject_id', 'A grading policy already exists for this class and subject. Edit the existing one instead.');
            }

            $components = collect($this->input('components', []))
                ->filter(function ($component): bool {
                    if (!is_array($component)) {
                        return false;
                    }

                    return !empty($component['component_name'])
                        || !empty($component['component_code'])
                        || data_get($component, 'total_marks') !== null
                        || data_get($component, 'total_marks') === '0'
                        || data_get($component, 'pass_marks') !== null
                        || data_get($component, 'pass_marks') === '0';
                })
                ->values();

            if ($components->isEmpty()) {
                return;
            }

            $codes = [];
            $componentTotal = 0.0;
            foreach ($components as $index => $component) {
                $rowNo = $index + 1;
                $name = trim((string) ($component['component_name'] ?? ''));
                $code = trim((string) ($component['component_code'] ?? ''));
                $total = $component['total_marks'] ?? null;
                $pass = $component['pass_marks'] ?? null;

                if ($name === '' || $code === '' || $total === null || $total === '') {
                    $validator->errors()->add('components', "Component row {$rowNo} requires name, code, and total marks.");
                    continue;
                }

                $normalizedCode = strtolower($code);
                if (in_array($normalizedCode, $codes, true)) {
                    $validator->errors()->add('components', "Duplicate component code '{$code}' in row {$rowNo}.");
                }
                $codes[] = $normalizedCode;

                $totalValue = (float) $total;
                $componentTotal += $totalValue;

                if ($pass !== null && $pass !== '' && (float) $pass > $totalValue) {
                    $validator->errors()->add('components', "Component pass marks cannot exceed total marks in row {$rowNo}.");
                }
            }

            $totalMarks = (float) $this->input('total_marks', 0);
            if (abs($componentTotal - $totalMarks) > 0.00001) {
                $validator->errors()->add('components', 'Sum of component total marks must equal policy total marks.');
            }
        });
    }
}
