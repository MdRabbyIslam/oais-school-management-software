<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admission_application_id');
            $table->foreign('admission_application_id', 'adm_doc_app_id_fk')
                ->references('id')->on('admission_applications')
                ->onDelete('cascade');
            $table->string('type')->nullable(); // e.g., birth_certificate, marksheet
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_documents');
    }
};
