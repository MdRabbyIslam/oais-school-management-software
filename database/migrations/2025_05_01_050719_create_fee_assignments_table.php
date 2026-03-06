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
        Schema::create('fee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_id')->constrained();
            $table->foreignId('term_id')->nullable()->constrained();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->json('meta')->nullable(); // Custom data
            $table->text('change_reason')->nullable(); // Track all modifications
            $table->json('history')->nullable(); // Store previous values
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['status', 'due_date']);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the index before dropping the table
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_date']);
        });
        Schema::dropIfExists('fee_assignments');

    }
};
