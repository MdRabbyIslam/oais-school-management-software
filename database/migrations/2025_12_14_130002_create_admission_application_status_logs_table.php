<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_application_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admission_application_id');
            $table->foreign('admission_application_id', 'adm_app_stat_app_id_fk')
                ->references('id')->on('admission_applications')
                ->onDelete('cascade');
            $table->enum('from_status', ['pending','under_review','approved','rejected'])->nullable();
            $table->enum('to_status', ['pending','under_review','approved','rejected']);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_application_status_logs');
    }
};
