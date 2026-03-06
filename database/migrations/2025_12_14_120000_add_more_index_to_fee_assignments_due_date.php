<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreIndexToFeeAssignmentsDueDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            // composite index to speed up queries filtering by student_id and ordering by due_date
            $table->index(['student_id', 'due_date'], 'idx_fee_assignments_student_due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_fee_assignments_student_due_date');
        });
    }
}
