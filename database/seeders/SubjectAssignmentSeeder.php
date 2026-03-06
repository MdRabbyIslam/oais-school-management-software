<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubjectAssignment;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Teacher;

class SubjectAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::first();
        $section = Section::first();
        $teacher = Teacher::first();

        if ($subject && $section && $teacher) {
            SubjectAssignment::create([
                'subject_id' => $subject->id,
                'section_id' => $section->id,
                'teacher_id' => $teacher->id,
            ]);
        }
    }
}
