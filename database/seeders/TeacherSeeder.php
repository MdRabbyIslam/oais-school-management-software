<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        Teacher::create([
            'teacher_id' => Str::uuid(),
            'name' => 'Alice Rahman',
            'qualification' => 'M.Sc. in Physics',
            'experience' => '5 years of high school teaching',
            'contact_info' => 'alice@example.com',
            'base_salary' => 50000.00,
            'status' => 'Active',
        ]);

        Teacher::create([
            'teacher_id' => Str::uuid(),
            'name' => 'John Karim',
            'qualification' => 'B.Ed. in Math',
            'experience' => '3 years of middle school teaching',
            'contact_info' => 'john@example.com',
            'base_salary' => 42000.00,
            'status' => 'Active',
        ]);
    }
}
