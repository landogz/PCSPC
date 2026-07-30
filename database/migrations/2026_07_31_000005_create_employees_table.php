<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('employee_number', 50)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 20)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('mobile', 30)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position_title')->nullable();
            $table->string('employment_status', 30)->default('active')->index();
            $table->date('date_hired')->nullable();
            $table->date('date_regularized')->nullable();
            $table->date('date_separated')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('civil_status', 30)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->text('tin')->nullable();
            $table->text('sss_number')->nullable();
            $table->text('philhealth_number')->nullable();
            $table->text('pagibig_number')->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
