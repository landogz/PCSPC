<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_career_histories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('position_title', 150);
            $table->string('employment_category', 50);
            $table->text('basic_salary')->nullable(); // encrypted at rest
            $table->string('salary_rate_type', 20)->default('monthly');
            $table->string('currency', 3)->default('PHP');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
            $table->index(['employee_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_career_histories');
    }
};
