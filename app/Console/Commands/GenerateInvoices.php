<?php

namespace App\Console\Commands;

use App\Models\{FeeAssignment, Invoice, InvoiceItem, AcademicYear};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Throwable;

class GenerateInvoices extends Command
{
    protected $signature = 'invoices:generate
                            {--student= : Generate for specific student}
                            {--period= : Filter by billing period (monthly,termly,annual)}
                            {--force : Regenerate invoices for due fees}
                            {--month= : Target month (format: YYYY-MM)}
                            {--generate_invoice_for_date= : Use this date instead of today (format: YYYY-MM-DD)}';


    protected $description = 'Generate invoices for due fee assignments and update next due dates';

    public function handle()
    {
        $invoiceDate = $this->getInvoiceDate();

        $query = FeeAssignment::with(['student', 'fee', 'studentEnrollment.academicYear'])
            ->active();

        if ($month = $this->option('month')) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('due_date', $date->year)
                ->whereMonth('due_date', $date->month);
        } else {
            $query->where('due_date', '<=', $invoiceDate);
        }

        if ($studentId = $this->option('student')) {
            $query->where('student_id', $studentId);
        }

        if ($period = $this->option('period')) {
            $query->whereHas('fee', fn($q) => $q->where('frequency', $period));
        }

        // Stream results ordered by student
        $query->orderBy('student_id')
          ->orderBy('id');

        $generatedInvoices   = 0;
        $failedInvoices     = 0;
        $currentStudentId    = null;
        $currentAssignments  = collect();

        foreach ($query->cursor() as $assignment) {
            if ($currentStudentId !== null && $assignment->student_id !== $currentStudentId) {
                // finished previous student
                $this->processStudentInvoices(
                    $currentStudentId,
                    $currentAssignments,
                    $generatedInvoices,
                    $failedInvoices
                );

                $currentAssignments = collect();
            }

            $currentStudentId = $assignment->student_id;
            $currentAssignments->push($assignment);
        }


        // Flush last student (if any)
        if ($currentStudentId !== null && $currentAssignments->isNotEmpty()) {
            DB::transaction(function () use (&$generatedInvoices, $currentStudentId, $currentAssignments) {
                if ($this->createStudentInvoice($currentStudentId, $currentAssignments)) {
                    $generatedInvoices++;
                }
            });
        }

        $this->info("Successfully generated {$generatedInvoices} invoices.");

         if ($failedInvoices > 0) {
            $this->warn("Failed to generate {$failedInvoices} student invoices. Check logs for details.");
        }
    }


    /**
     * Wraps one student's invoice generation in a transaction and logs hard failures.
     */
    protected function processStudentInvoices(
        int $studentId,
        Collection $assignments,
        int &$generatedInvoices,
        int &$failedInvoices
    ): void {
        if ($assignments->isEmpty()) {
            return;
        }

        try {
            DB::transaction(function () use (&$generatedInvoices, $studentId, $assignments) {
                if ($this->createStudentInvoice($studentId, $assignments)) {
                    $generatedInvoices++;
                }
            });
        } catch (Throwable $e) {
            $failedInvoices++;

            Log::error('Failed to generate invoice for student.', [
                'student_id'       => $studentId,
                'fee_assignment_ids' => $assignments->pluck('id')->all(),
                'invoice_date'     => $this->getInvoiceDate()->toDateString(),
                'options'          => [
                    'student'  => $this->option('student'),
                    'period'   => $this->option('period'),
                    'month'    => $this->option('month'),
                    'force'    => $this->option('force'),
                    'generate_invoice_for_date' => $this->option('generate_invoice_for_date'),
                ],
                'message'          => $e->getMessage(),
                'exception'        => $e,
            ]);

            $this->error("Failed generating invoice for student {$studentId}. See logs for details.");
            // transaction automatically rolled back
        }
    }


    protected function createStudentInvoice($studentId, $feeAssignments)
    {
        $invoiceItems = [];
        $totalAmount = 0;
        $earliestDueDate = null;

        $periodsMap = [];

        foreach ($feeAssignments as $assignment) {



            // Get all uninvoiced periods (including missed ones)
            $periodsToInvoice = $this->getUninvoicedPeriods($assignment);

            if (empty($periodsToInvoice)) {
                $this->warn("No uninvoiced periods for fee: {$assignment->fee->fee_name}");
                continue;
            }

            foreach ($periodsToInvoice as $period) {
                $invoiceItems[] = [
                    'fee_assignment_id' => $assignment->id,
                    'description' => $this->getPeriodDescription($assignment, $period),
                    'amount' => $assignment->amount
                ];
                $totalAmount += $assignment->amount;
            }

            $this->info("Invoiced {$assignment->fee->fee_name} for student {$studentId} in periods: " . implode(', ', $periodsToInvoice));
            $this->info("Total amount for this assignment: {$totalAmount}");
            $this->info("Due date for this assignment: {$assignment->due_date}");

            // $earliestDueDate = min(
            //     $earliestDueDate ?? now()->addDays(14),
            //     $assignment->due_date
            // );

            $earliestDueDate = $assignment->due_date;

            // $earliestDueDate = min(
            //     $earliestDueDate ?? now()->addDays(14),
            //     $assignment->due_date
            // );

            // $this->info("Earliest date for this assignment: {$earliestDueDate}");


            $periodsMap[$assignment->id] = $periodsToInvoice;

        }

        // dd([
        //     'student_id' => $studentId,
        //     'invoice_items' => $invoiceItems,
        //     'total_amount' => $totalAmount,
        //     'earliest_due_date' => $earliestDueDate,
        //     'periods_map' => $periodsMap
        // ]);

        if (empty($invoiceItems)) {
            $this->warn("No invoiceable fees found for student {$studentId}");
            return false;
        }

        // dd($invoiceItems);

        $invoice = Invoice::create([
            'student_id' => $studentId,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => $this->getInvoiceDate(),
            'due_date' => $earliestDueDate,
            'total_amount' => $totalAmount,
            'status' => 'issued'
        ]);

        $invoice->items()->createMany($invoiceItems);



        // Update tracking for all invoiced periods
        foreach ($feeAssignments as $assignment) {

            try {

                $this->updateAssignmentTracking($assignment, $periodsMap[$assignment->id]);

            } catch (\Throwable $th) {

                $this->warn("Failed to update tracking for FeeAssignment ID {$assignment->id}: {$th->getMessage()}");

                Log::error('Failed to update tracking after invoice.', [
                    'fee_assignment_id' => $assignment->id,
                    'student_id'        => $assignment->student_id,
                    'invoice_date'      => $this->getInvoiceDate()->toDateString(),
                    'periods_invoiced'  => $periodsMap[$assignment->id] ?? [],
                    'message'           => $th->getMessage(),
                    'exception'         => $th,
                ]);

            }


        }

        return true;
    }

    protected function getUninvoicedPeriods($assignment)
    {
        $uninvoiced = [];
        $currentDate = $this->getInvoiceDate();
        $periodFormat = 'Y-m';

        // For one-time fees
        if ($assignment->fee->billing_type === 'one-time') {
            return $assignment->isInvoicedForPeriod() ? [] : [$assignment->due_date->format($periodFormat)];
        }

        if($assignment->fee->billing_type == 'term-based'){

            // should bill only once per term
            if ($assignment->isInvoicedForPeriod()) {
                return [];
            }
            return  [$assignment->due_date->format($periodFormat)];

        }

        // For recurring fees
        $start = $assignment->due_date ?? $assignment->created_at->startOfMonth();
        $end = $assignment->end_date ?? $currentDate->endOfMonth();

        // Enforce enrollment/academic-year cutoff: prefer student_enrollment, then student's active enrollment
        $enrollment = $assignment->studentEnrollment ?? ($assignment->student->activeEnrollment ?? null);
        if ($enrollment) {
            // If enrollment has completion_date, use it; otherwise try the academic year's end_date
            if (!empty($enrollment->completion_date)) {
                $limit = \Carbon\Carbon::parse($enrollment->completion_date)->endOfDay();
            } else if (!empty($enrollment->academic_year_id)) {
                $ay = AcademicYear::find($enrollment->academic_year_id);
                if ($ay) {
                    $limit = \Carbon\Carbon::parse($ay->end_date)->endOfDay();
                }
            }

            if (isset($limit)) {
                $end = $end->copy()->min($limit);
            }
        }

        $period = $start->copy();
        while ($period <= $end) {
            $periodKey = $period->format($periodFormat);

            if (!$assignment->isInvoicedForPeriod($periodKey) &&
                $period <= $currentDate) {
                $uninvoiced[] = $periodKey;
            }

            // Move to next period based on frequency
            $period = match ($assignment->fee->frequency) {
                'monthly' => $period->addMonth(),
                'quarterly' => $period->addMonths(3),
                'termly' => $period->addMonths(4),
                'annual' => $period->addYear(),
                default => $period->addMonth(),
            };
        }

        return $uninvoiced;
    }



    /**
     * Mark each invoiced period in meta, then bump due_date or complete.
     *
     * @param  \App\Models\FeeAssignment  $assignment
     * @param  array  $periodsInvoiced    // <<< NEW: pass in the ['YYYY-MM', …] keys you just invoiced
     * @return void
     */
    protected function updateAssignmentTracking($assignment, array $periodsInvoiced)
    {
        // 1) Load and append newly invoiced periods
        $meta = $assignment->meta ?? [];
        foreach ($periodsInvoiced as $periodKey) {
            // only write each key once
            if (empty($meta['invoiced_periods'][$periodKey])) {
                $meta['invoiced_periods'][$periodKey] = $this->getInvoiceDate();
            }
        }
        $assignment->update(['meta' => $meta]);

        // 2) For one-time fees, we’re done
        if ($assignment->fee->billing_type === 'one-time') {
            $assignment->update(['status' => 'completed']);
            return;
        }

        // 3) Otherwise, compute the next due_date based on all invoiced_periods
        $nextDueDate = $this->calculateNextDueDate($assignment);
        if ($nextDueDate) {
            $assignment->update(['due_date' => $nextDueDate]);
        } else {
            $assignment->update(['status' => 'completed']);
        }

    }



    /**
     * Generates a human-readable description for the fee + period
     */
    protected function getPeriodDescription($assignment, $period)
    {
        $this->info($period);

        $desc = $assignment->fee->fee_name;

        // Add term info if available
        if ($assignment->term_id && $term = $assignment->term) {
            $desc .= " ({$term->name})";
        }

        // Add period info for recurring fees
        if ($assignment->fee->billing_type !== 'one-time') {
            // $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $period);
            $periodDate = \Carbon\Carbon::createFromFormat('Y-m-d', "$period-01");

            $this->info($periodDate->format('M Y'));
            $desc .= " - " . $periodDate->format('M Y');

            // For late invoices, add "(Late)" indicator
            if ($periodDate->lt($this->getInvoiceDate()->startOfMonth())) {
                $desc .= " (Late)";
            }
            $this->info($desc);

        }

        return $desc;
    }


    /**
     * Calculates the next due date after invoicing current periods.
     *
     * @param  \App\Models\FeeAssignment  $assignment
     * @return \Carbon\Carbon|null
     */
    protected function calculateNextDueDate(FeeAssignment $assignment)
    {
        // 1) One-time fees never repeat
        if ($assignment->fee->billing_type === 'one-time' || $assignment->fee->billing_type === 'term-based') {
            return null;
        }

        // 2) Grab the original day-of-month you seeded on the assignment
        $originalDay = $assignment->due_date->day;

        // 3) Collect all invoiced YYYY-MM keys
        $periodKeys = array_keys($assignment->meta['invoiced_periods'] ?? []);

        if (empty($periodKeys)) {
            // no invoices yet → start from your original due_date
            $base = $assignment->due_date->copy();
        } else {
            // pick the latest period (e.g. "2025-05") and build a Carbon at that month + original day
            $lastPeriod = collect($periodKeys)->sort()->last();
            $base = Carbon::createFromFormat('Y-m-d', $lastPeriod . '-' . str_pad($originalDay, 2, '0', STR_PAD_LEFT));
        }

        // 4) Add according to frequency
        $next = match ($assignment->fee->frequency) {
            'monthly'   => $base->addMonth(),
            'quarterly' => $base->addMonths(3),
            'termly'    => $base->addMonths(4),
            'annual'    => $base->addYear(),
            default     => $base->addMonth(),
        };

        // 5) Don’t exceed an end_date
        if ($assignment->end_date && $next->gt($assignment->end_date)) {
            return null;
        }

        return $next;
    }


    protected function generateInvoiceNumber()
    {
        $count = Invoice::count() + 1;
        return 'INV-' . $this->getInvoiceDate()->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    protected function getInvoiceDate(): Carbon
    {
        // 1. Explicit date wins
        if ($date = $this->option('generate_invoice_for_date')) {
            return Carbon::parse($date);
        }

        // 2. If a month was passed, use the end of that month
        if ($month = $this->option('month')) {
            return Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        }

        // 3. Fallback: today
        return now();
    }

}
