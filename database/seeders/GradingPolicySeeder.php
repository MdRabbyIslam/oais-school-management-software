<?php

namespace Database\Seeders;

use App\Models\GradeScheme;
use App\Models\GradingPolicy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradingPolicySeeder extends Seeder
{
    /**
     * Seed starter grading policies for existing class-subject mappings.
     *
     * Rules:
     * - For 100 marks: nursery-class 2 and class 3+ use different schemes.
     * - For 50 marks: only nursery-class 2 gets policy by default.
     */
    public function run(): void
    {
        $class3Above100 = GradeScheme::where('name', '100-Mark Class 3+ (A+ 80-100)')->first();
        $nurseryToClass2_100 = GradeScheme::where('name', '100-Mark Nursery-Class 2 (A+ 90-100)')->first();
        // $nurseryToClass2_50 = GradeScheme::where('name', '50-Mark Nursery-Class 2 (A+ 45-50)')->first();

        // if (!$class3Above100 || !$nurseryToClass2_100 || !$nurseryToClass2_50) {
        if (!$class3Above100 || !$nurseryToClass2_100 ) {
            return;
        }

        $pairs = DB::table('class_subject as cs')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->select('cs.class_id', 'cs.subject_id', 'c.class_level')
            ->get();

        foreach ($pairs as $pair) {
            $isLowerClass = ((int) $pair->class_level <= 4);
            $schemeFor100 = $isLowerClass ? $nurseryToClass2_100 : $class3Above100;

            GradingPolicy::updateOrCreate(
                [
                    'class_id' => $pair->class_id,
                    'subject_id' => $pair->subject_id,
                    'total_marks' => 100.00,
                ],
                [
                    'pass_marks' => 40.00,
                    'grade_scheme_id' => $schemeFor100->id,
                    'is_active' => true,
                ]
            );

            // if ($isLowerClass) {
            //     GradingPolicy::updateOrCreate(
            //         [
            //             'class_id' => $pair->class_id,
            //             'subject_id' => $pair->subject_id,
            //             'total_marks' => 50.00,
            //         ],
            //         [
            //             'pass_marks' => 20.00,
            //             'grade_scheme_id' => $nurseryToClass2_50->id,
            //             'is_active' => true,
            //         ]
            //     );
            // } else {
            //     GradingPolicy::where('class_id', $pair->class_id)
            //         ->where('subject_id', $pair->subject_id)
            //         ->where('total_marks', 50.00)
            //         ->delete();
            // }
        }
    }
}
