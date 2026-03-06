<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use Carbon\Carbon;

class UpcomingAcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $current = AcademicYear::current();

        if ($current) {
            $start = Carbon::parse($current->start_date)->addYear()->startOfDay();
            $end = Carbon::parse($current->end_date)->addYear()->startOfDay();
        } else {
            $start = Carbon::now()->addYear()->startOfYear();
            $end = $start->copy()->endOfYear();
        }

        $name = $start->format('Y') . '-' . $end->format('Y');

        $exists = AcademicYear::whereDate('start_date', $start->toDateString())->exists();

        if (! $exists) {
            AcademicYear::create([
                'name' => $name,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'is_current' => false,
                'is_admissions_open' => false,
            ]);
            $this->command->info("Created upcoming academic year: {$name}");
        } else {
            $this->command->info("Upcoming academic year {$name} already exists.");
        }
    }
}
