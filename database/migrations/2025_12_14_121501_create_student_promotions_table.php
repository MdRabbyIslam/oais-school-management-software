<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('from_enrollment_id');
            $table->unsignedBigInteger('to_enrollment_id')->nullable();
            $table->unsignedBigInteger('target_academic_year_id')->nullable();
            $table->unsignedBigInteger('target_class_id')->nullable();
            $table->unsignedBigInteger('target_section_id')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('auto_promotion')->default(false);
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('from_enrollment_id');
            $table->index('to_enrollment_id');

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('from_enrollment_id')->references('id')->on('student_enrollments')->onDelete('cascade');
            $table->foreign('to_enrollment_id')->references('id')->on('student_enrollments')->onDelete('set null');
            $table->foreign('target_academic_year_id')->references('id')->on('academic_years')->onDelete('set null');
            $table->foreign('target_class_id')->references('id')->on('classes')->onDelete('set null');
            $table->foreign('target_section_id')->references('id')->on('sections')->onDelete('set null');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_promotions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['from_enrollment_id']);
            $table->dropForeign(['to_enrollment_id']);
            $table->dropForeign(['target_academic_year_id']);
            $table->dropForeign(['target_class_id']);
            $table->dropForeign(['target_section_id']);
            $table->dropForeign(['requested_by_user_id']);
            $table->dropForeign(['reviewed_by_user_id']);
        });
        Schema::dropIfExists('student_promotions');
    }
}
