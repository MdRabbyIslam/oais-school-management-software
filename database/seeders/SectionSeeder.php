<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\SchoolClass;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $classes = SchoolClass::all();

        foreach ($classes as $class) {
            Section::create([
                'class_id' => $class->id,
                'section_name' => 'A',
            ]);
            Section::create([
                'class_id' => $class->id,
                'section_name' => 'B',
            ]);
        }
    }
}
