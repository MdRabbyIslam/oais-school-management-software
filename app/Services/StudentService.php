<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentService
{
    /**
     * Regenerate student_id for all existing students in the format OMS-{Year}-{Serial}.
     */
    public function regenerateStudentIds(): void
    {
        DB::transaction(function () {
            // fetch all students, ordered by admission_date then id
            $all = Student::orderBy('admission_date')
                          ->orderBy('id')
                          ->get()
                          ->groupBy(fn($s) => Carbon::parse($s->admission_date)->format('Y'));

            foreach ($all as $year => $students) {
                $serial = 1;
                foreach ($students as $student) {
                    $student->student_id = sprintf('OMS-%s-%03d', $year, $serial++);
                    $student->save();
                }
            }
        });
    }
}
