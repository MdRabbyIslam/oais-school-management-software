<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToFeeAssignmentsDueDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            // single-column index to speed up ORDER BY on due_date
            $table->index('due_date', 'idx_fee_assignments_due_date');
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
            $table->dropIndex('idx_fee_assignments_due_date');
        });
    }
}
