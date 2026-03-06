<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FeeAssignment;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillFeeAssignmentsEnrollment extends Command
{
    protected $signature = 'fees:backfill-enrollment {--apply : Actually write changes} {--chunk=500 : Chunk size}';

    protected $description = 'Backfill fee_assignments.student_enrollment_id by matching assignments to student_enrollments';

    public function handle()
    {
        $apply = $this->option('apply');
        $chunkSize = (int) $this->option('chunk');
        // Avoid accumulating query log entries during long runs
        DB::connection()->disableQueryLog();

        // Only process assignments that do not already have an enrollment linked
        // Eager load 'fee' to check billing type and frequency
        $query = FeeAssignment::whereNull('student_enrollment_id')->with('fee')->orderBy('id');
        $total = $query->count();
        $this->info("Found {$total} fee_assignments without enrollment link.");

        $processed = 0;
        $assigned = 0;
        $ambiguous = 0;
        $skipped = 0;

        // Set a cutoff date for old recurring monthly fees
        // $cutoffForOldRecurring = Carbon::now()->subDays(31);

        $query->chunkById($chunkSize, function($assignments) use (&$processed, &$assigned, &$ambiguous, &$skipped, $apply) {
            foreach ($assignments as $a) {
                $processed++;

                $due = $a->due_date ? Carbon::parse($a->due_date) : null;



                // Before matching: if student has an active enrollment, and the assignment due_date
                // is strictly after that enrollment's completion (or academic year end), mark it completed.
                $active = StudentEnrollment::where('student_id', $a->student_id)
                    ->where('status', 'active')
                    ->first(['id', 'completion_date', 'academic_year_id', 'status', 'enrollment_date']);

                if ($active && $due) {
                    $limit = null;
                    if (!empty($active->completion_date)) {
                        $limit = Carbon::parse($active->completion_date)->endOfDay();
                    } elseif (!empty($active->academic_year_id)) {
                        $ay = AcademicYear::find($active->academic_year_id);
                        if ($ay) {
                            $limit = Carbon::parse($ay->end_date)->endOfDay();
                        }
                    }

                    if ($a->fee && $a->fee->billing_type === 'recurring' && $a->fee->frequency === 'monthly') {
                        if ($due && $limit && $due->diffInDays($limit) <= 31) {
                            $this->line("#{$a->id}: Recurring monthly fee due_date ({$due->toDateString()}) is not older than 31 days from completion/academic_year end date — marking completed");
                            if ($apply) {
                                $a->status = 'completed';
                                $a->save();
                            }
                            $assigned++; // Counted as handled/completed
                        }
                    }

                }

                // 1) Try to find enrollment where due_date falls between enrollment_date and completion_date (or open-ended)
                $matches = StudentEnrollment::where('student_id', $a->student_id)
                    ->when($due, function($q) use ($due) {
                        $q->where(function($q2) use ($due) {
                            $q2->whereNull('enrollment_date')->orWhereDate('enrollment_date', '<=', $due->toDateString());
                        })->where(function($q3) use ($due) {
                            $q3->whereNull('completion_date')->orWhereDate('completion_date', '>=', $due->toDateString());
                        });
                    })
                    ->limit(2)
                    ->get(['id', 'enrollment_date', 'completion_date', 'academic_year_id', 'status']);


                if ($matches->count() === 1) {
                    $en = $matches->first();
                    $this->line("#{$a->id}: matched to enrollment {$en->id} via date check");
                    if ($apply) {
                        $a->student_enrollment_id = $en->id;
                        $a->save();
                    }
                    $assigned++;
                    continue;
                }

                // 2) If no unique match, try match by academic year containing due_date
                if ($due) {
                    $ay = AcademicYear::whereDate('start_date', '<=', $due->toDateString())
                        ->whereDate('end_date', '>=', $due->toDateString())
                        ->first();

                    if ($ay) {
                        $matches = StudentEnrollment::where('student_id', $a->student_id)
                            ->where('academic_year_id', $ay->id)
                            ->limit(2)
                            ->get(['id', 'enrollment_date', 'completion_date', 'academic_year_id', 'status']);

                        if ($matches->count() === 1) {
                            $en = $matches->first();
                            $this->line("#{$a->id}: matched to enrollment {$en->id} via academic year {$ay->id}");
                            if ($apply) {
                                $a->student_enrollment_id = $en->id;
                                $a->save();
                            }
                            $assigned++;
                            continue;
                        }

                        if ($matches->count() > 1) {
                            $this->line("#{$a->id}: ambiguous matches ({$matches->count()}) for academic year {$ay->id}");
                            $ambiguous++;
                            continue;
                        }
                    }
                }

                // 3) fallback: no match
                $this->line("#{$a->id}: no match found");
                $skipped++;
                // free potential large variables
                unset($matches, $en, $ay, $active, $limit, $due);
            }
        });

        $this->info("Processed: {$processed}");
        $this->info("Assigned: {$assigned}");
        $this->info("Ambiguous: {$ambiguous}");
        $this->info("Skipped: {$skipped}");

        if (! $apply) {
            $this->warn('Dry-run only. Re-run with --apply to persist changes.');
        }

        return 0;
    }
}
