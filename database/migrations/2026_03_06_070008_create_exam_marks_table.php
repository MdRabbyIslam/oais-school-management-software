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
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_subject_id')->constrained('exam_assessment_subjects')->onDelete('cascade');
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->string('remarks')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_subject_id', 'student_enrollment_id'], 'exam_marks_subject_enrollment_unique');
            $table->index('student_enrollment_id', 'exam_marks_enrollment_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_marks');
    }
};

