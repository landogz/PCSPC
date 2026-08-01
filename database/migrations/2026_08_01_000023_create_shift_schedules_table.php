<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->string('assignee_type', 20); // employee | department
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->json('days_of_week')->nullable(); // 1=Mon … 7=Sun
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assignee_type', 'employee_id']);
            $table->index(['assignee_type', 'department_id']);
            $table->index(['effective_from', 'effective_to']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
    }
};
