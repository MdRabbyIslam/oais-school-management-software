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
        Schema::create('student_term_extra_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->decimal('homework_marks', 6, 2)->nullable();
            $table->decimal('attendance_marks', 6, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['academic_year_id', 'term_id', 'class_id', 'student_enrollment_id'],
                'student_term_extra_marks_unique'
            );
            $table->index(['term_id', 'class_id'], 'student_term_extra_marks_term_class_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_term_extra_marks');
    }
};

