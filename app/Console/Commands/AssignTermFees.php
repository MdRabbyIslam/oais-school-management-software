<?php

// app/Console/Commands/AssignTermFees.php
namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Term;
use App\Models\Fee;
use App\Models\FeeAssignment;
use Illuminate\Console\Command;

class AssignTermFees extends Command
{
    protected $signature = 'fees:assign
                            {term : Term ID}
                            {--class= : Specific class ID}
                            {--dry-run : Show what would be assigned}';

    protected $description = 'Assign term-based fees to students';

    public function handle()
    {
        $term = Term::findOrFail($this->argument('term'));
        $classId = $this->option('class');

        // Get term-based fees with amounts
        $fees = Fee::where('billing_type', 'term-based')
                  ->with(['termFeeAmounts' => fn($q) => $q->where('term_id', $term->id)])
                  ->get()
                  ->filter(fn($fee) => $fee->termFeeAmounts->isNotEmpty());

        if ($fees->isEmpty()) {
            return $this->error('No term-based fees found for this term!');
        }

        // Prefer active enrollment for class/section lookup
        $query = Student::query();
        if ($classId) {
            // find students who currently have an active enrollment for this class
            $query->whereHas('enrollments', function($q) use ($classId) {
                $q->where('class_id', $classId)->where('status', 'active');
            });
        }

        $students = $query->get();

        $this->info("Assigning {$fees->count()} fees to {$students->count()} students...");

        foreach ($students as $student) {
            foreach ($fees as $fee) {
                $amount = $fee->termFeeAmounts->first()->amount;

                if ($this->option('dry-run')) {
                    $this->line("[DRY RUN] Assigning {$fee->fee_name} ({$amount}) to {$student->name}");
                    continue;
                }

                // Attempt to find student's active enrollment for the term's academic year
                $enrollment = $student->enrollments()->where('academic_year_id', $term->academic_year_id)
                    ->where(function($q) { $q->whereNull('completion_date')->orWhere('status', 'active'); })
                    ->first();

                $data = [
                    'student_id' => $student->id,
                    'fee_id' => $fee->id,
                    'term_id' => $term->id
                ];

                $values = [
                    'amount' => $amount,
                    'due_date' => $term->fee_due_date ?? $term->end_date->subDays(14),
                    'status' => 'active'
                ];

                if ($enrollment) {
                    $values['student_enrollment_id'] = $enrollment->id;
                }

                FeeAssignment::updateOrCreate($data, $values);
            }
        }

        $this->info("Assignment completed for Term: {$term->name}");
    }
}
