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
        Schema::create('grading_policy_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_policy_id')->constrained('grading_policies')->onDelete('cascade');
            $table->string('component_name', 100);
            $table->string('component_code', 30);
            $table->decimal('total_marks', 6, 2);
            $table->decimal('pass_marks', 6, 2)->nullable();
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['grading_policy_id', 'component_code'], 'grading_policy_components_policy_code_unique');
            $table->index(['grading_policy_id', 'sort_order'], 'grading_policy_components_policy_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_policy_components');
    }
};

