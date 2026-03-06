<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnsureActiveEnrollmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $current = AcademicYear::current();

        if (! $current) {
            $this->command->info('No current academic year found — skipping enrollment creation.');
            return;
        }

        $students = Student::all();
        $created = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Only consider students with an assigned section (treated as "active")
                if (! $student->section_id) {
                    continue;
                }

                $hasActive = StudentEnrollment::where('student_id', $student->id)
                    ->where('academic_year_id', $current->id)
                    ->where('status', 'active')
                    ->exists();

                if ($hasActive) {
                    continue;
                }

                $classId = null;
                if ($student->section) {
                    $classId = $student->section->class_id ?? null;
                }

                StudentEnrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $current->id,
                    'class_id' => $classId,
                    'section_id' => $student->section_id,
                    'roll_number' => $student->roll_number,
                    'enrollment_date' => $student->admission_date ?? Carbon::now()->toDateString(),
                    'status' => 'active',
                    'meta' => null,
                ]);

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->command->info("EnsureActiveEnrollmentsSeeder: created {$created} enrollment(s).\n");
    }
}
