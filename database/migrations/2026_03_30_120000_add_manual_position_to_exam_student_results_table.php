<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_student_results', function (Blueprint $table) {
            $table->integer('manual_position')->nullable()->after('position');
            $table->index(['assessment_class_id', 'manual_position'], 'exam_student_results_class_manual_position_idx');
        });
    }

    public function down(): void
    {
        Schema::table('exam_student_results', function (Blueprint $table) {
            $table->dropIndex('exam_student_results_class_manual_position_idx');
            $table->dropColumn('manual_position');
        });
    }
};
