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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('externalId');
            $table->integer('externalPersonId');
            $table->boolean('isActive');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('genderAsString');
            $table->dateTime('dateJoining');
            $table->dateTime('dateLeaving')->nullable();
            $table->dateTime('dateElection');
            $table->foreignId('party_id');
            $table->foreignId('parl_group_id')->nullable();
            $table->foreignId('canton_id')->nullable();
            $table->foreignId('council_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
