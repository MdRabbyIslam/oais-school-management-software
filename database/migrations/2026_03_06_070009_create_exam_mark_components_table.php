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
        Schema::create('exam_mark_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_mark_id')->constrained('exam_marks')->onDelete('cascade');
            $table->foreignId('assessment_subject_component_id')->constrained('exam_assessment_subject_components')->onDelete('cascade');
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['exam_mark_id', 'assessment_subject_component_id'], 'exam_mark_components_mark_component_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_mark_components');
    }
};

