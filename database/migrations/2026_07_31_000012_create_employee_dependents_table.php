<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 20)->nullable();
            $table->string('relationship', 30);
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->boolean('is_beneficiary')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'relationship']);
            $table->index(['employee_id', 'last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_dependents');
    }
};
