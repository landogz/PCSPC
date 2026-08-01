<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('institution', 150);
            $table->string('level', 30);
            $table->string('degree_or_course', 150)->nullable();
            $table->unsignedSmallInteger('year_started')->nullable();
            $table->unsignedSmallInteger('year_ended')->nullable();
            $table->boolean('is_highest')->default(false);
            $table->string('honors', 150)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'level']);
            $table->index(['employee_id', 'year_ended']);
        });

        Schema::create('employee_employment_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('employer_name', 150);
            $table->string('position_title', 150);
            $table->string('location', 150)->nullable();
            $table->date('date_from');
            $table->date('date_to')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'date_from']);
            $table->index(['employee_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_histories');
        Schema::dropIfExists('employee_educations');
    }
};
