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
        Schema::table('grading_policies', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('grade_scheme_id');
            $table->decimal('weight', 5, 2)->default(1.00)->after('is_optional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_policies', function (Blueprint $table) {
            $table->dropColumn(['is_optional', 'weight']);
        });
    }
};

