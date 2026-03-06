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
        Schema::create('exam_student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_class_id')->constrained('exam_assessment_classes')->onDelete('cascade');
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->decimal('total_obtained', 8, 2);
            $table->decimal('total_marks', 8, 2);
            $table->decimal('percentage', 5, 2);
            $table->decimal('gpa', 3, 2);
            $table->string('final_grade', 10);
            $table->integer('failed_subject_count')->default(0);
            $table->boolean('is_pass');
            $table->integer('position')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['assessment_class_id', 'student_enrollment_id'], 'exam_student_results_class_enrollment_unique');
            $table->index(['assessment_class_id', 'position'], 'exam_student_results_class_position_idx');
            $table->index('student_enrollment_id', 'exam_student_results_enrollment_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_student_results');
    }
};
