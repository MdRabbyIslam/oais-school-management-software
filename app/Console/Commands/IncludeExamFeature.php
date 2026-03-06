<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class IncludeExamFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:include-exam-feature';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Include exam feature setup tasks: fix class levels, truncate exam grading tables, and reseed grade data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating class_level to serial order based on classes.id ...');

        $before = DB::table('classes')
            ->select('id', 'name', 'class_level')
            ->orderBy('id')
            ->get();

        DB::transaction(function () {
            DB::statement('SET @lvl := 0');
            DB::statement('
                UPDATE classes c
                JOIN (
                    SELECT id, (@lvl := @lvl + 1) AS new_level
                    FROM classes
                    ORDER BY id
                ) x ON x.id = c.id
                SET c.class_level = x.new_level
            ');
        });

        $after = DB::table('classes')
            ->select('id', 'name', 'class_level')
            ->orderBy('id')
            ->get();

        $this->line('Before:');
        $this->table(['ID', 'Class Name', 'Class Level'], $before->map(fn ($r) => [$r->id, $r->name, $r->class_level])->all());

        $this->line('After:');
        $this->table(['ID', 'Class Name', 'Class Level'], $after->map(fn ($r) => [$r->id, $r->name, $r->class_level])->all());

        $this->info('Class level fix completed.');

        $this->warn('Truncating related exam grading tables ...');
        $this->truncateExamGradingTables();
        $this->info('Truncate completed.');

        $this->info('Running GradeSchemeSeeder ...');
        $this->callSeeder(\Database\Seeders\GradeSchemeSeeder::class);

        $this->info('Running GradingPolicySeeder ...');
        $this->callSeeder(\Database\Seeders\GradingPolicySeeder::class);

        $this->info('Exam grading seeders completed.');

        return self::SUCCESS;
    }

    private function truncateExamGradingTables(): void
    {
        $tables = [
            'exam_mark_components',
            'exam_marks',
            'exam_assessment_subject_components',
            'exam_assessment_subjects',
            'grading_policies',
            'grade_scheme_items',
            'grade_schemes',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function callSeeder(string $seederClass): void
    {
        Artisan::call('db:seed', [
            '--class' => $seederClass,
            '--force' => true,
        ]);

        $this->output->write(Artisan::output());
    }
}
