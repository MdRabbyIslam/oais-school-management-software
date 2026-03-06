<?php

namespace App\Console\Commands;

use App\Models\ClassFeeAmount;
use App\Models\Invoice;
use App\Models\InvoiceFee;
use App\Models\Student;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:monthly-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for students with recurring fees';

    /**
     * Execute the console command.
     */
    // public function handle()
    // {
    //     // Get all students along with their classes and fees (with fee amounts for each class)
    //     $students = Student::with(['schoolClass', 'fees.classFeeAmounts'])->get();

    //     foreach ($students as $student) {
    //         $class_id = $student->schoolClass->id;

    //         // Check if an invoice already exists for this student and the same due date
    //         $existingInvoice = Invoice::where('student_id', $student->id)
    //             ->where('due_date', now()->addMonth()->format('Y-m-d')) // Due date is one month from now
    //             ->first();

    //         // If an invoice already exists, skip generating a new one
    //         if ($existingInvoice) {
    //             $this->info("Invoice already exists for student {$student->name}.");
    //             continue;
    //         }

    //         $recurringFees = $student->fees()->where('recurring', true)->get();

    //         // Calculate the total due amount for the invoice by summing up the recurring fees
    //         $totalDue = 0;
    //         foreach ($recurringFees as $fee) {
    //             // Fetch the class fee amount for this fee and class
    //             $classFeeAmount = ClassFeeAmount::where(['class_id' => $class_id, 'fee_id' => $fee->id])->first();

    //             // If a class fee amount is found, add it to the total due
    //             if ($classFeeAmount) {
    //                 $totalDue += $classFeeAmount->amount;
    //             }
    //         }

    //         // Create the invoice
    //         $invoiceDate = now();
    //         $dueDate = now()->addMonth();  // Set due date to one month from now

    //         $invoice = Invoice::create([
    //             'student_id' => $student->id,
    //             'invoice_date' => $invoiceDate,
    //             'due_date' => $dueDate,
    //             'total_due' => $totalDue,
    //             'status' => 'Due',
    //         ]);

    //         // Add fees to the invoice, ensuring no duplicates
    //         foreach ($recurringFees as $fee) {
    //             // Check if this fee is already assigned to the student for the same period
    //             $existingInvoiceFee = $invoice->invoiceFees()->where('fee_id', $fee->id)->first();

    //             // If the fee is already added to the invoice, skip adding it again
    //             if ($existingInvoiceFee) {
    //                 $this->info("Fee '{$fee->fee_name}' is already assigned to the invoice.");
    //                 continue;
    //             }

    //             // Fetch the class fee amount
    //             $classFeeAmount = $fee->classFeeAmounts()->where(['class_id' => $class_id])->first();

    //             if ($classFeeAmount) {
    //                 // Add the fee to the invoice
    //                 $invoice->invoiceFees()->create([
    //                     'fee_id' => $fee->id,
    //                     'amount_due' => $classFeeAmount->amount,
    //                 ]);
    //             }
    //         }

    //         // Update total_due for the invoice after adding the fees
    //         $invoice->update(['total_due' => $invoice->invoiceFees->sum('amount_due')]);

    //         $this->info("Invoice generated successfully for student {$student->name}. Total Due: $totalDue");
    //     }
    // }

    public function handle()
    {
        $students = Student::with(['schoolClass', 'fees.classFeeAmounts'])
            ->whereHas('fees', function ($query) {
                $query->where('recurring', true);
            })
            ->get();

        if ($students->isEmpty()) {
            $this->warn("No students with recurring fees found.");
            return;
        }

        foreach ($students as $student) {
            if (!$student->schoolClass) {
                $this->error("Student {$student->name} has no assigned class. Skipping.");
                continue;
            }

            $classId = $student->schoolClass->id;
            $dueDate = now()->addMonth()->format('Y-m-d');

            // Check for existing invoice for this month
            if (Invoice::where('student_id', $student->id)
                ->where('due_date', $dueDate)
                ->exists()) {
                $this->info("Invoice already exists for student {$student->name}.");
                continue;
            }

            $recurringFees = $student->fees()
                ->where('recurring', true)
                ->get();

            $totalDue = 0;
            $invoiceFees = [];

            foreach ($recurringFees as $fee) {
                $classFeeAmount = $fee->classFeeAmounts()
                    ->where('class_id', $classId)
                    ->first();

                if (!$classFeeAmount) {
                    $this->error("Fee '{$fee->fee_name}' has no amount set for class ID {$classId}.");
                    continue;
                }

                $totalDue += $classFeeAmount->amount;
                $invoiceFees[] = [
                    'fee_id' => $fee->id,
                    'amount_due' => $classFeeAmount->amount,
                ];
            }

            if ($totalDue <= 0) {
                $this->warn("No fees to invoice for student {$student->name}.");
                continue;
            }

            $invoice = Invoice::create([
                'student_id' => $student->id,
                'invoice_date' => now(),
                'due_date' => $dueDate,
                'total_due' => $totalDue,
                'status' => 'Due',
            ]);

            $invoice->invoiceFees()->createMany($invoiceFees);
            $this->info("Invoice #{$invoice->id} generated for {$student->name}. Total: {$totalDue}");
        }
    }
}
