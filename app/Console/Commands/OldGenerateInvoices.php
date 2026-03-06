<?php

namespace App\Console\Commands;

use App\Models\{FeeAssignment, Invoice};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OldGenerateInvoices extends Command
{
    protected $signature = 'old_invoices:generate
                            {--student= : Generate for specific student}
                            {--period=monthly : billing period (monthly, termly)}
                            {--month= : Target month (format: YYYY-MM)}';

    protected $description = 'Generate invoices based on active fee assignments';

    public function handle()
    {
        $query = FeeAssignment::with(['student', 'fee'])
            ->active()
            ->whereHas('fee', fn($q) => $q->where('frequency', $this->option('period')));

        if ($studentId = $this->option('student')) {
            $query->where('student_id', $studentId);
        }

        if ($month = $this->option('month')) {
            $query->where('due_date', 'like', "{$month}%");
        } else {
            $query->where('due_date', '<=', now()->addDays(14));
        }

        $assignments = $query->get()->groupBy('student_id');

        if ($assignments->isEmpty()) {
            return $this->error('No active fee assignments found for the criteria');
        }

        DB::transaction(function () use ($assignments) {
            foreach ($assignments as $studentId => $fees) {
                $this->createInvoice($studentId, $fees);
            }
        });

        $this->info("Generated invoices for ".count($assignments)." students");
    }

    protected function createInvoice($studentId, $assignments)
    {
        $invoice = Invoice::create([
            'student_id' => $studentId,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => $assignments->first()->due_date,
            'total_amount' => $assignments->sum('amount')
        ]);

        foreach ($assignments as $assignment) {
            $invoice->items()->create([
                'fee_assignment_id' => $assignment->id,
                'description' => $this->getFeeDescription($assignment),
                'amount' => $assignment->amount
            ]);
        }
    }

    protected function generateInvoiceNumber()
    {
        return 'INV-'.now()->format('Ymd-His').'-'.str_pad(Invoice::count()+1, 4, '0', STR_PAD_LEFT);
    }

    protected function getFeeDescription($assignment)
    {
        $desc = $assignment->fee->fee_name;

        if ($assignment->term_id) {
            $desc .= " ({$assignment->term->name})";
        }

        if ($assignment->isRecurring()) {
            $desc .= " - ".$assignment->due_date->format('M Y');
        }

        return $desc;
    }
}
