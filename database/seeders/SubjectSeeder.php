<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        Subject::insert([
            [
                'name' => 'Mathematics',
                'code' => 'MATH101',
                'description' => 'Basic Mathematics for Grade 1-5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'English',
                'code' => 'ENG101',
                'description' => 'Basic English Grammar and Vocabulary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
