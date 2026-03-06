<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;

class ClassSeeder extends Seeder
{
    public function run()
    {
        $classes = [
            ['name' => 'Class 1', 'class_level' => 1],
            ['name' => 'Class 2', 'class_level' => 2],
            ['name' => 'Class 3', 'class_level' => 3],
            ['name' => 'Class 4', 'class_level' => 4],
            ['name' => 'Class 5', 'class_level' => 5],
            ['name' => 'Class 6', 'class_level' => 6],
            ['name' => 'Class 7', 'class_level' => 7],
            ['name' => 'Class 8', 'class_level' => 8],
            ['name' => 'Class 9', 'class_level' => 9],
            ['name' => 'Class 10', 'class_level' => 10],
        ];

        foreach ($classes as $class) {
            SchoolClass::create($class);
        }
    }
}
