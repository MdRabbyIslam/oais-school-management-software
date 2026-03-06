<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FeeAssignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdjustFeeDueDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:adjust-due-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks FeeAssignment due_dates after 2025-12-31 and adjusts them one month prior.';

    /**
     * Execute the console command.
     *
     * @return int
     */

public function handle()
{
    // Define your cutoff date and chunk size
    $cutoffDate = Carbon::create(2025, 12, 31);
    $chunkSize = 100; // You can adjust this size based on performance
    $totalAdjustedCount = 0;

    $this->info("Starting memory-efficient due date adjustment for assignments with due_date > {$cutoffDate->toDateString()}...");

    try {
        DB::beginTransaction();

        // 1. Get the total count for the progress bar (optional, but good UX)
        $totalCount = FeeAssignment::query()
            ->select(['id'])
            ->where('due_date', '>', $cutoffDate)
            // Use withoutGlobalScopes() to ignore any default eager loading
            ->withoutGlobalScopes()
            ->count();

        if ($totalCount === 0) {
            $this->comment('No fee assignments found requiring a due date adjustment.');
            DB::commit();
            return 0;
        }

        $this->warn("Found {$totalCount} assignments to adjust. Processing in chunks of {$chunkSize}...");

        $bar = $this->output->createProgressBar($totalCount);

        // 2. Use the chunk method to process records in batches
        FeeAssignment::query()
        ->select(['id','due_date'])
        ->where('due_date', '>', $cutoffDate)
        // Use withoutGlobalScopes() again before chunking
        ->withoutGlobalScopes()
        ->chunk($chunkSize, function ($assignments) use ($bar, &$totalAdjustedCount) {

            foreach ($assignments as $assignment) {
                // No changes needed here, as the save()/update() operation
                // only touches the FeeAssignment table.
                $originalDate = clone $assignment->due_date;
                $newDate = $assignment->due_date->subMonthNoOverflow();

                $assignment->due_date = $newDate;
                $assignment->status = 'completed';
                $assignment->save();

                // ... (logging and counting)
                $totalAdjustedCount++;
                $bar->advance();
            }
        });

        $bar->finish();
        DB::commit();

        $this->info("\n\nSuccessfully adjusted {$totalAdjustedCount} fee assignment due dates.");

        return 0; // Success
    } catch (\Exception $e) {
        DB::rollBack();
        $this->error("\n\nAn error occurred during the update process: " . $e->getMessage());
        return 1; // Failure
    }
}
}
