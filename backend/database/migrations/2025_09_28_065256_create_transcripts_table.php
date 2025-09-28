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
        Schema::create('transcripts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('externalId');
            $table->longText('text');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('languageOfText')->nullable();
            $table->foreignId('business_id')->nullable();
            $table->foreignId('parl_session_id');
            $table->foreignId('council_id')->nullable();
            $table->foreignId('member_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcripts');
    }
};
