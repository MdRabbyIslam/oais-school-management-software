<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_assessments', function (Blueprint $table) {
            $table->string('result_calculation_mode', 50)
                ->default('standard_weighted')
                ->after('status');
        });

        Schema::table('grading_policies', function (Blueprint $table) {
            $table->boolean('exclude_from_final_gpa')
                ->default(false)
                ->after('grade_scheme_id');
            $table->boolean('is_fourth_subject_eligible')
                ->default(false)
                ->after('exclude_from_final_gpa');
        });

        Schema::table('exam_assessment_subjects', function (Blueprint $table) {
            $table->boolean('exclude_from_final_gpa')
                ->default(false)
                ->after('pass_marks');
            $table->boolean('is_fourth_subject_eligible')
                ->default(false)
                ->after('exclude_from_final_gpa');
        });

        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreignId('optional_subject_id')
                ->nullable()
                ->after('class_id')
                ->constrained('subjects')
                ->nullOnDelete();
        });

        DB::table('grading_policies')->update([
            'exclude_from_final_gpa' => DB::raw('is_optional'),
            'is_fourth_subject_eligible' => DB::raw('is_optional'),
        ]);

        DB::table('exam_assessment_subjects')->update([
            'exclude_from_final_gpa' => DB::raw('is_optional'),
            'is_fourth_subject_eligible' => DB::raw('is_optional'),
        ]);
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('optional_subject_id');
        });

        Schema::table('exam_assessment_subjects', function (Blueprint $table) {
            $table->dropColumn(['exclude_from_final_gpa', 'is_fourth_subject_eligible']);
        });

        Schema::table('grading_policies', function (Blueprint $table) {
            $table->dropColumn(['exclude_from_final_gpa', 'is_fourth_subject_eligible']);
        });

        Schema::table('exam_assessments', function (Blueprint $table) {
            $table->dropColumn('result_calculation_mode');
        });
    }
};
