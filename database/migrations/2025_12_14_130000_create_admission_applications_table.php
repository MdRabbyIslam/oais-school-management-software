<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->nullable()->unique();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('restrict');

            $table->unsignedBigInteger('preferred_class_id')->nullable();
            $table->unsignedBigInteger('preferred_section_id')->nullable();

            // applicant fields
            $table->string('name');
            $table->date('dob')->nullable();
            $table->string('primary_guardian_name')->nullable();
            $table->string('primary_guardian_contact')->nullable();
            $table->string('primary_guardian_relation')->nullable();
            $table->string('secondary_guardian_name')->nullable();
            $table->string('secondary_guardian_contact')->nullable();
            $table->string('secondary_guardian_relation')->nullable();
            $table->string('blood_group')->nullable();
            $table->text('address')->nullable();

            // workflow
            $table->enum('status', ['pending','under_review','approved','rejected'])->default('pending');
            $table->enum('source', ['internal','public'])->default('internal');

            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->unsignedBigInteger('approved_student_id')->nullable();
            $table->foreign('approved_student_id')->references('id')->on('students')->nullOnDelete();

            $table->text('review_notes')->nullable();
            $table->text('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_applications');
    }
};
