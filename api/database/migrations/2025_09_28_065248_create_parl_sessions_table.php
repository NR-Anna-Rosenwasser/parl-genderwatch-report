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
        Schema::create('parl_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('externalId');
            $table->integer('number');
            $table->string('name');
            $table->string('abbreviation');
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->string('title')->nullable();
            $table->integer('type')->nullable();
            $table->string('typeName')->nullable();
            $table->dateTime('modified');
            $table->integer('legislativePeriodNumber');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parl_sessions');
    }
};
