<?php

namespace Database\Seeders;

use App\Models\GradeScheme;
use App\Models\GradeSchemeItem;
use Illuminate\Database\Seeder;

class GradeSchemeSeeder extends Seeder
{
    /**
     * Seed reusable grade schemes and grade ranges.
     */
    public function run(): void
    {
        $class3Above100 = $this->upsertScheme(
            '100-Mark Class 3+ (A+ 80-100)',
            'Applicable for class 3 and above.'
        );
        $this->syncItems($class3Above100->id, [
            ['grade' => 'A+', 'gpa' => 5.00, 'min' => 80, 'max' => 100, 'order' => 1],
            ['grade' => 'A',  'gpa' => 4.00, 'min' => 70, 'max' => 79,  'order' => 2],
            ['grade' => 'A-', 'gpa' => 3.50, 'min' => 60, 'max' => 69,  'order' => 3],
            ['grade' => 'B',  'gpa' => 3.00, 'min' => 50, 'max' => 59,  'order' => 4],
            ['grade' => 'C',  'gpa' => 2.00, 'min' => 40, 'max' => 49,  'order' => 5],
            ['grade' => 'F',  'gpa' => 0.00, 'min' => 1,  'max' => 39,  'order' => 6],
        ]);

        $nurseryToClass2_100 = $this->upsertScheme(
            '100-Mark Nursery-Class 2 (A+ 90-100)',
            'Applicable for nursery to class 2.'
        );
        $this->syncItems($nurseryToClass2_100->id, [
            ['grade' => 'A+', 'gpa' => 5.00, 'min' => 90, 'max' => 100, 'order' => 1],
            ['grade' => 'A',  'gpa' => 4.00, 'min' => 80, 'max' => 89,  'order' => 2],
            ['grade' => 'A-', 'gpa' => 3.50, 'min' => 70, 'max' => 79,  'order' => 3],
            ['grade' => 'B+', 'gpa' => 3.00, 'min' => 60, 'max' => 69,  'order' => 4],
            ['grade' => 'B',  'gpa' => 2.50, 'min' => 50, 'max' => 59,  'order' => 5],
            ['grade' => 'C',  'gpa' => 2.00, 'min' => 40, 'max' => 49,  'order' => 6],
            ['grade' => 'F',  'gpa' => 0.00, 'min' => 1,  'max' => 39,  'order' => 7],
        ]);

        $nurseryToClass2_50 = $this->upsertScheme(
            '50-Mark Nursery-Class 2 (A+ 45-50)',
            'Applicable for nursery to class 2.'
        );
        $this->syncItems($nurseryToClass2_50->id, [
            ['grade' => 'A+', 'gpa' => 5.00, 'min' => 45, 'max' => 50, 'order' => 1],
            ['grade' => 'A',  'gpa' => 4.00, 'min' => 40, 'max' => 44, 'order' => 2],
            ['grade' => 'A-', 'gpa' => 3.50, 'min' => 35, 'max' => 39, 'order' => 3],
            ['grade' => 'B+', 'gpa' => 3.00, 'min' => 30, 'max' => 34, 'order' => 4],
            ['grade' => 'B',  'gpa' => 2.50, 'min' => 25, 'max' => 29, 'order' => 5],
            ['grade' => 'C',  'gpa' => 2.00, 'min' => 20, 'max' => 24, 'order' => 6],
            ['grade' => 'F',  'gpa' => 0.00, 'min' => 1,  'max' => 19, 'order' => 7],
        ]);

        GradeScheme::whereIn('name', [
            '100-Mark Strict (A+ 91-100)',
            '100-Mark Standard (A+ 81-100)',
            '50-Mark Standard (A+ 40-50)',
        ])->update(['is_active' => false]);
    }

    private function upsertScheme(string $name, string $description): GradeScheme
    {
        return GradeScheme::updateOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'is_active' => true,
            ]
        );
    }

    /**
     * @param array<int, array{grade:string,gpa:float,min:int,max:int,order:int}> $items
     */
    private function syncItems(int $gradeSchemeId, array $items): void
    {
        $grades = array_column($items, 'grade');
        GradeSchemeItem::where('grade_scheme_id', $gradeSchemeId)
            ->whereNotIn('letter_grade', $grades)
            ->delete();

        foreach ($items as $item) {
            GradeSchemeItem::updateOrCreate(
                [
                    'grade_scheme_id' => $gradeSchemeId,
                    'letter_grade' => $item['grade'],
                ],
                [
                    'gpa' => $item['gpa'],
                    'min_mark' => $item['min'],
                    'max_mark' => $item['max'],
                    'sort_order' => $item['order'],
                ]
            );
        }
    }
}
