<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradeSchemeRequest;
use App\Http\Requests\UpdateGradeSchemeRequest;
use App\Models\GradeScheme;
use App\Models\GradeSchemeItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeSchemeController extends Controller
{
    public function index()
    {
        $this->authorize('manage-exams');

        $gradeSchemes = GradeScheme::withCount('items')
            ->latest()
            ->paginate(20);

        return view('pages.grade_schemes_index', compact('gradeSchemes'));
    }

    public function create()
    {
        $this->authorize('manage-exams');

        return view('pages.grade_schemes_create');
    }

    public function store(StoreGradeSchemeRequest $request)
    {
        $this->authorize('manage-exams');

        DB::transaction(function () use ($request) {
            $scheme = GradeScheme::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => (bool) $request->boolean('is_active', true),
            ]);

            $this->syncItems($scheme, $request->input('items', []));
        });

        return redirect()->route('grade-schemes.index')->with('success', 'Grade scheme created successfully.');
    }

    public function edit(GradeScheme $gradeScheme)
    {
        $this->authorize('manage-exams');

        $gradeScheme->load(['items' => fn ($q) => $q->orderBy('sort_order')]);

        return view('pages.grade_schemes_edit', compact('gradeScheme'));
    }

    public function update(UpdateGradeSchemeRequest $request, GradeScheme $gradeScheme)
    {
        $this->authorize('manage-exams');

        DB::transaction(function () use ($request, $gradeScheme) {
            $gradeScheme->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => (bool) $request->boolean('is_active', true),
            ]);

            $this->syncItems($gradeScheme, $request->input('items', []));
        });

        return redirect()->route('grade-schemes.index')->with('success', 'Grade scheme updated successfully.');
    }

    public function destroy(GradeScheme $gradeScheme)
    {
        $this->authorize('manage-exams');

        $inUse = $gradeScheme->gradingPolicies()->exists();
        if ($inUse) {
            return back()->with('error', 'Cannot delete grade scheme because it is used by grading policies.');
        }

        $gradeScheme->delete();

        return redirect()->route('grade-schemes.index')->with('success', 'Grade scheme deleted successfully.');
    }

    private function syncItems(GradeScheme $scheme, array $items): void
    {
        $normalized = collect($items)
            ->map(function ($item, $index) {
                $grade = trim((string) ($item['letter_grade'] ?? ''));
                $gpa = $item['gpa'] ?? null;
                $min = $item['min_mark'] ?? null;
                $max = $item['max_mark'] ?? null;

                if ($grade === '' && $gpa === null && $min === null && $max === null) {
                    return null;
                }

                return [
                    'letter_grade' => $grade,
                    'gpa' => $gpa,
                    'min_mark' => $min,
                    'max_mark' => $max,
                    'sort_order' => (int) ($item['sort_order'] ?? ($index + 1)),
                ];
            })
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'At least one valid grade range item is required.',
            ]);
        }

        $duplicateGrades = $normalized->pluck('letter_grade')->duplicates();
        if ($duplicateGrades->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate letter grades are not allowed inside one scheme.',
            ]);
        }

        $duplicateGpa = $normalized->pluck('gpa')->duplicates();
        if ($duplicateGpa->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate GPA values are not allowed inside one scheme.',
            ]);
        }

        foreach ($normalized as $row) {
            if ($row['letter_grade'] === '' || $row['gpa'] === null || $row['min_mark'] === null || $row['max_mark'] === null) {
                throw ValidationException::withMessages([
                    'items' => 'Each grade row must include letter grade, GPA, min mark, and max mark.',
                ]);
            }

            if ((float) $row['min_mark'] > (float) $row['max_mark']) {
                throw ValidationException::withMessages([
                    'items' => "Min mark cannot be greater than max mark for {$row['letter_grade']}.",
                ]);
            }
        }

        $sorted = $normalized->sortBy('min_mark')->values();
        for ($i = 1; $i < $sorted->count(); $i++) {
            $prev = $sorted[$i - 1];
            $curr = $sorted[$i];
            if ((float) $curr['min_mark'] <= (float) $prev['max_mark']) {
                throw ValidationException::withMessages([
                    'items' => "Mark ranges overlap between {$prev['letter_grade']} ({$prev['min_mark']}-{$prev['max_mark']}) and {$curr['letter_grade']} ({$curr['min_mark']}-{$curr['max_mark']}).",
                ]);
            }
        }

        $existingIds = [];
        foreach ($normalized as $row) {
            $item = GradeSchemeItem::updateOrCreate(
                [
                    'grade_scheme_id' => $scheme->id,
                    'letter_grade' => $row['letter_grade'],
                ],
                [
                    'gpa' => $row['gpa'],
                    'min_mark' => $row['min_mark'],
                    'max_mark' => $row['max_mark'],
                    'sort_order' => $row['sort_order'],
                ]
            );
            $existingIds[] = $item->id;
        }

        $scheme->items()->whereNotIn('id', $existingIds)->delete();
    }
}
