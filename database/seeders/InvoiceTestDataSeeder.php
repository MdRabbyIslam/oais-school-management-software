<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeAssignment;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {



        // Create test term
        // $term = Term::create([
        //     'academic_year_id' => 1,
        //     'order' => 1,
        //     'name' => 'Term 1 2024',
        //     'start_date' => '2024-01-01',
        //     'end_date' => '2024-06-30'
        // ]);

        $monthlyTuition = Fee::where('fee_name', 'Monthly Tuition')->first();

        if (!$monthlyTuition) {
            // Create test fees
            $monthlyTuition = Fee::create([
                'fee_group_id' => 1,
                'fee_name' => 'Monthly Tuition',
                'billing_type' => 'recurring',
                'frequency' => 'monthly',
                'is_mandatory' => true
            ]);
        }

        $annualFee = Fee::where('fee_name', 'Annual Fee')->first();
        if (!$annualFee) {
            $annualFee = Fee::create([
                'fee_group_id' => 1,
                'fee_name' => 'Annual Registration',
                'billing_type' => 'one-time',
                'is_mandatory' => true
            ]);
        }






        // Get 5 random students
        $students = Student::inRandomOrder()->take(5)->get();

        // Assign fees to students
        foreach ($students as $student) {
            FeeAssignment::create([
                'student_id' => $student->id,
                'fee_id' => $monthlyTuition->id,
                'amount' => rand(5000, 10000), // 5000-10000 BDT
                'due_date' => "2025-01-05",
                'status' => 'active'
            ]);

            // Annual fee
            FeeAssignment::create([
                'student_id' => $student->id,
                'fee_id' => $annualFee->id,
                'amount' => 15000,
                'due_date' => '2025-01-15',
                'status' => 'active'
            ]);
        }

        // $this->command->info('Generated:');
        // $this->command->info('- 1 term');
        $this->command->info('- 2 fees');
        $this->command->info('- 5 students with fee assignments');
    }
}
