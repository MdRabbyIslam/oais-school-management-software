<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class StoreGradeSchemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.letter_grade' => ['nullable', 'string', 'max:10'],
            'items.*.gpa' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'items.*.min_mark' => ['nullable', 'numeric', 'min:0'],
            'items.*.max_mark' => ['nullable', 'numeric', 'min:0'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rows = $this->normalizedItems();

            if ($rows->isEmpty()) {
                $validator->errors()->add('items', 'At least one valid grade row is required.');
                return;
            }

            foreach ($rows as $row) {
                if ($row['letter_grade'] === '' || $row['gpa'] === null || $row['min_mark'] === null || $row['max_mark'] === null) {
                    $validator->errors()->add('items', 'Each grade row must include letter grade, GPA, min mark and max mark.');
                    return;
                }

                if ($row['min_mark'] > $row['max_mark']) {
                    $validator->errors()->add('items', "Min mark cannot be greater than max mark for {$row['letter_grade']}.");
                    return;
                }
            }

            if ($rows->pluck('letter_grade')->duplicates()->isNotEmpty()) {
                $validator->errors()->add('items', 'Duplicate letter grades are not allowed.');
                return;
            }

            if ($rows->pluck('gpa')->duplicates()->isNotEmpty()) {
                $validator->errors()->add('items', 'Duplicate GPA values are not allowed.');
                return;
            }

            if ($rows->pluck('min_mark')->duplicates()->isNotEmpty() || $rows->pluck('max_mark')->duplicates()->isNotEmpty()) {
                $validator->errors()->add('items', 'Duplicate min/max mark boundaries are not allowed.');
                return;
            }

            $sorted = $rows->sortBy('min_mark')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];

                if ($curr['min_mark'] <= $prev['max_mark']) {
                    $validator->errors()->add(
                        'items',
                        "Mark ranges overlap between {$prev['letter_grade']} ({$prev['min_mark']}-{$prev['max_mark']}) and {$curr['letter_grade']} ({$curr['min_mark']}-{$curr['max_mark']})."
                    );
                    return;
                }
            }
        });
    }

    private function normalizedItems(): Collection
    {
        return collect($this->input('items', []))
            ->map(function ($item) {
                $grade = trim((string) ($item['letter_grade'] ?? ''));
                $gpa = $item['gpa'] ?? null;
                $min = $item['min_mark'] ?? null;
                $max = $item['max_mark'] ?? null;

                if ($grade === '' && ($gpa === null || $gpa === '') && ($min === null || $min === '') && ($max === null || $max === '')) {
                    return null;
                }

                return [
                    'letter_grade' => $grade,
                    'gpa' => ($gpa === '' ? null : (float) $gpa),
                    'min_mark' => ($min === '' ? null : (float) $min),
                    'max_mark' => ($max === '' ? null : (float) $max),
                ];
            })
            ->filter()
            ->values();
    }
}
