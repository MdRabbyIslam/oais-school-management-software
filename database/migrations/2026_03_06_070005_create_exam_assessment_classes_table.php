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
        Schema::create('exam_assessment_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_assessment_id')->constrained('exam_assessments')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->boolean('is_published')->default(false);
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_assessment_id', 'class_id'], 'exam_assessment_classes_assessment_class_unique');
            $table->index('class_id', 'exam_assessment_classes_class_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_assessment_classes');
    }
};
