<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->date('holiday_date');
            $table->string('type', 40)->default('regular');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_double_pay')->default(false);
            $table->unsignedTinyInteger('paid_hours')->default(8);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['holiday_date', 'name']);
            $table->index(['holiday_date', 'is_active']);
            $table->index(['type', 'is_active']);
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('time_in', 5);
            $table->string('time_out', 5);
            $table->unsignedSmallInteger('break_minutes')->default(60);
            $table->unsignedSmallInteger('grace_minutes')->default(0);
            $table->boolean('crosses_midnight')->default(false);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('holidays');
    }
};
