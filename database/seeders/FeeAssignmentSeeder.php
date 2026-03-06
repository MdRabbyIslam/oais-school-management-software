<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Fee;
use App\Models\FeeGroup;
use App\Models\SchoolClass;
use Carbon\Carbon;

class FeeAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Fetch all students and fees
        $students = Student::all();
        $fees = Fee::all();
        $classes = SchoolClass::all();  // Assuming you have multiple classes

        // Assign fees to students with random due dates, start dates, and end dates
        foreach ($students as $student) {
            foreach ($fees as $fee) {
                // Random due date within 30 days
                $dueDate = Carbon::now()->addDays(rand(1, 30));
                // Random service start date (within 3 months from now)
                $startDate = Carbon::now()->addMonths(rand(1, 3));
                // Random service end date (add 1 to 3 months from start)
                $endDate = $startDate->addMonths(rand(1, 3));

                $student->feeAssignments()->create([
                    'fee_id' => $fee->id,
                    'due_date' => $dueDate,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_active' => true,
                    'is_service_opted' => $fee->is_service_fee ? rand(0, 1) : true,  // Randomly decide if the service is opted (only for service fees)
                ]);
            }
        }

        // Optionally, you can create invoices after assigning fees (for testing)
        // Call your invoice generation command here if needed
    }
}
