<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_ledger_entries', function (Blueprint $table): void {
            // Accrual keys are YYYY-MM; leave-use keys are "req-{uuid}" (~40 chars).
            $table->string('period_key', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_ledger_entries', function (Blueprint $table): void {
            $table->string('period_key', 20)->nullable()->change();
        });
    }
};
