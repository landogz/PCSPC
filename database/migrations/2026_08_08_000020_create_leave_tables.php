<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->boolean('is_accruing')->default(false);
            $table->boolean('requires_reason')->default(true);
            $table->boolean('requires_hr')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->unsignedSmallInteger('leave_year');
            $table->decimal('beginning', 10, 2)->default(0);
            $table->decimal('earned', 10, 2)->default(0);
            $table->decimal('used', 10, 2)->default(0);
            $table->decimal('adjusted', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'leave_year'], 'leave_balances_employee_type_year_unique');
            $table->index(['leave_year', 'leave_type_id']);
        });

        Schema::create('leave_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('leave_balance_id')->constrained('leave_balances')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->string('entry_type', 20);
            $table->decimal('amount', 10, 2);
            $table->date('effective_date');
            $table->string('period_key', 64)->nullable();
            $table->string('reason', 500)->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'leave_type_id', 'entry_type']);
            $table->index(['leave_balance_id', 'entry_type', 'period_key'], 'leave_ledger_balance_type_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_ledger_entries');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
