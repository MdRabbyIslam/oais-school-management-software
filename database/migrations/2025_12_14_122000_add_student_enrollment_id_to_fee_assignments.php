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
            $table->unsignedBigInteger('student_enrollment_id')->nullable()->after('student_id');
            $table->index('student_enrollment_id');
            $table->foreign('student_enrollment_id')->references('id')->on('student_enrollments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_assignments', function (Blueprint $table) {
            $table->dropForeign(['student_enrollment_id']);
            $table->dropIndex(['student_enrollment_id']);
            $table->dropColumn('student_enrollment_id');
        });
    }
};
