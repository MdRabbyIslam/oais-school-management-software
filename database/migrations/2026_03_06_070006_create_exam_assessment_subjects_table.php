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
        Schema::create('exam_assessment_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_class_id')->constrained('exam_assessment_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('grading_policy_id')->constrained('grading_policies')->onDelete('restrict');
            $table->decimal('total_marks', 6, 2);
            $table->decimal('pass_marks', 6, 2);
            $table->boolean('is_optional')->default(false);
            // Relative contribution of this subject in final result calculation.
            // Example: 1.00 = full weight, 0.50 = half weight.
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['assessment_class_id', 'subject_id'], 'exam_assessment_subjects_class_subject_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_assessment_subjects');
    }
};
