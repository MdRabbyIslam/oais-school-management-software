<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Section;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $sections = Section::where('class_id', '<=', 5)->get();

        foreach ($sections as $key => $section) {
            Student::create([

                'student_id' => Str::uuid(),
                'name' => 'John Doe ' . $key,
                'dob' => '2012-06-15',
                'primary_guardian_name' => 'Mr. Doe',
                'primary_guardian_contact' => '01710000000',
                'primary_guardian_relation' => 'Father',
                'secondary_guardian_name' => 'Mrs. Doe',
                'secondary_guardian_contact' => '01710000001',
                'secondary_guardian_relation' => 'Mother',
                'address' => '123 Main St',
                'admission_date' => now(),
                'roll_number' => rand(1, 100),
                'section_id' => $section->id,
            ]);
        }
    }
}
