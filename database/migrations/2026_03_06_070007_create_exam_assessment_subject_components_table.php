<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_assessment_subject_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_subject_id')->constrained('exam_assessment_subjects')->onDelete('cascade');
            $table->string('component_name');
            $table->string('component_code', 30);
            $table->decimal('total_marks', 6, 2);
            $table->decimal('pass_marks', 6, 2)->nullable();
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['assessment_subject_id', 'component_code'], 'exam_subject_components_subject_code_unique');
            $table->index(['assessment_subject_id', 'sort_order'], 'exam_subject_components_subject_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_assessment_subject_components');
    }
};
