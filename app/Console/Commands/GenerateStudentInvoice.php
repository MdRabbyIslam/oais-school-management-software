<?php

namespace App\Console\Commands;

use App\Models\{FeeAssignment, Invoice};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateStudentInvoice extends Command
{
    protected $signature = 'old-invoices:generate-student {student : Student ID} {--due-days=14 : Include fees due within X days}';

    protected $description = 'Generate invoice for a specific student';

    public function handle()
    {
        $studentId = $this->argument('student');
        $dueDays = $this->option('due-days');

        $assignments = FeeAssignment::with(['student', 'fee'])
            ->pendingInvoicing()
            ->where('student_id', $studentId)
            ->where('due_date', '<=', now()->addDays( (int) $dueDays))
            ->get();

        // dd($assignments);

        if ($assignments->isEmpty()) {
            return $this->error("No pending fees found for student ID: {$studentId}");
        }

        DB::transaction(function () use ($assignments, $studentId) {
            $invoice = Invoice::create([
                'student_id' => $studentId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'total_amount' => $assignments->sum('amount'),
                'status' => 'issued'
            ]);

            $assignments->each(function ($fee) use ($invoice) {
                $invoice->items()->create([
                    'fee_assignment_id' => $fee->id,
                    'description' => $this->getFeeDescription($fee),
                    'amount' => $fee->amount
                ]);
                $fee->markAsInvoiced();
            });
        });

        $this->info("Successfully generated invoice for student ID: {$studentId}");
    }

    protected function generateInvoiceNumber()
    {
        return 'INV-' . now()->format('Ymd') . '-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT);
    }

    protected function getFeeDescription(FeeAssignment $fee)
    {
        $parts = [$fee->fee->fee_name];

        if ($fee->term_id) {
            $parts[] = $fee->term->name;
        }

        if ($fee->fee->feeGroup) {
            $parts[] = '(' . $fee->fee->feeGroup->name . ')';
        }

        return implode(' ', $parts);
    }
}
