<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Student;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::limit(5)->get();

        foreach ($students as $student) {
            Attendance::create([
                'student_id' => $student->id,
                'date' => now()->format('Y-m-d'),
                'status' => rand(0, 1) ? 'Present' : 'Absent',
            ]);
        }
    }
}
