<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('employee_number', 50)->nullable()->unique()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('mfa_secret');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('password_changed_at')->nullable()->after('locked_until');
            $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'employee_number',
                'is_active',
                'mfa_enabled',
                'mfa_secret',
                'failed_login_attempts',
                'locked_until',
                'password_changed_at',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
