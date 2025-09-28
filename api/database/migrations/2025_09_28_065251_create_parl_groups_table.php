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
        Schema::create('parl_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->integer('externalId');
            $table->integer('number');
            $table->boolean('isActive');
            $table->string('code');
            $table->string('name');
            $table->string('abbreviation');
            $table->dateTime('nameUsedSince')->nullable();
            $table->dateTime('modified');
            $table->string('colour', 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parl_groups');
    }
};
