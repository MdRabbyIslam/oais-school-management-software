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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('name');
            $table->date('dob');
            $table->string('primary_guardian_name');
            $table->string('primary_guardian_contact', 20);
            $table->string('primary_guardian_relation');
            $table->string('secondary_guardian_name')->nullable();
            $table->string('secondary_guardian_contact', 20)->nullable();
            $table->string('secondary_guardian_relation')->nullable();
            $table->text('address')->nullable();
            $table->date('admission_date');
            $table->unsignedInteger('roll_number')->nullable();
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->string('birth_certificate_path')->nullable();
            $table->string('marksheet_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
