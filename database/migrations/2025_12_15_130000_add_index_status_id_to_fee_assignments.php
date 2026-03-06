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
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->index(['status', 'id'], 'idx_fee_assignments_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_fee_assignments_status_id');
        });
    }
};
