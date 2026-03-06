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
        Schema::create('grade_scheme_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scheme_id')->constrained('grade_schemes')->onDelete('cascade');
            $table->string('letter_grade', 10);
            $table->decimal('gpa', 3, 2);
            $table->decimal('min_mark', 6, 2);
            $table->decimal('max_mark', 6, 2);
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['grade_scheme_id', 'letter_grade'], 'grade_scheme_items_scheme_grade_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_scheme_items');
    }
};

