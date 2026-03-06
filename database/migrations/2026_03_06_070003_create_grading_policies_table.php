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
        Schema::create('grading_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->decimal('total_marks', 6, 2);
            $table->decimal('pass_marks', 6, 2);
            $table->foreignId('grade_scheme_id')->constrained('grade_schemes')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['class_id', 'subject_id', 'total_marks'], 'grading_policies_class_subject_total_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_policies');
    }
};

