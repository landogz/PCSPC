<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'security'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'security'],
            ['name' => 'View Audit Log', 'slug' => 'audit.view', 'module' => 'audit'],
            ['name' => 'Manage Administration', 'slug' => 'administration.manage', 'module' => 'administration'],
            ['name' => 'View Employees', 'slug' => 'employees.view', 'module' => 'employee'],
            ['name' => 'Manage Employees', 'slug' => 'employees.manage', 'module' => 'employee'],
            ['name' => 'File Leave', 'slug' => 'leave.file', 'module' => 'leave'],
            ['name' => 'Approve Leave', 'slug' => 'leave.approve', 'module' => 'leave'],
            ['name' => 'View Timekeeping', 'slug' => 'timekeeping.view', 'module' => 'timekeeping'],
        ];

        foreach ($permissions as $permission) {
            $model = Permission::query()->firstOrNew(['slug' => $permission['slug']]);
            if (! $model->exists) {
                $model->uuid = (string) Str::uuid();
            }
            $model->fill($permission)->save();
        }

        $superAdmin = $this->upsertRole('super-admin', 'Super Admin', 'Full system access', true);
        $hrAdmin = $this->upsertRole('hr-admin', 'HR Admin', 'HR operations administrator', false);
        $employee = $this->upsertRole('employee', 'Employee', 'Standard employee self-service', false);

        $superAdmin->permissions()->sync(Permission::query()->pluck('id'));
        $hrAdmin->permissions()->sync(
            Permission::query()->whereIn('slug', [
                'dashboard.view',
                'users.manage',
                'roles.manage',
                'audit.view',
                'administration.manage',
                'employees.view',
                'employees.manage',
                'leave.file',
                'leave.approve',
                'timekeeping.view',
            ])->pluck('id')
        );
        $employee->permissions()->sync(
            Permission::query()->whereIn('slug', [
                'dashboard.view',
                'leave.file',
                'timekeeping.view',
            ])->pluck('id')
        );

        $admin = $this->upsertUser(
            'admin@pcspc.local',
            'System Administrator',
            'EMP-0001',
            false,
        );
        $admin->roles()->sync([$hrAdmin->id, $employee->id]);

        $demoEmployee = $this->upsertUser(
            'employee@pcspc.local',
            'Demo Employee',
            'EMP-1001',
            false,
        );
        $demoEmployee->roles()->sync([$employee->id]);

        $mfaUser = $this->upsertUser(
            'mfa@pcspc.local',
            'MFA Demo Admin',
            'EMP-0002',
            true,
        );
        $mfaUser->roles()->sync([$superAdmin->id]);

        $departments = [
            ['code' => 'HR', 'name' => 'Human Resources', 'description' => 'People operations and employee services'],
            ['code' => 'IT', 'name' => 'Information Technology', 'description' => 'Systems, infrastructure, and support'],
            ['code' => 'FIN', 'name' => 'Finance', 'description' => 'Accounting and financial controls'],
            ['code' => 'OPS', 'name' => 'Operations', 'description' => 'Day-to-day operational units'],
        ];

        foreach ($departments as $department) {
            $model = Department::query()->firstOrNew(['code' => $department['code']]);
            if (! $model->exists) {
                $model->uuid = (string) Str::uuid();
            }
            $model->fill([
                ...$department,
                'is_active' => true,
            ])->save();
        }
    }

    private function upsertRole(string $slug, string $name, string $description, bool $requiresMfa): Role
    {
        $role = Role::query()->firstOrNew(['slug' => $slug]);
        if (! $role->exists) {
            $role->uuid = (string) Str::uuid();
        }
        $role->fill([
            'name' => $name,
            'description' => $description,
            'requires_mfa' => $requiresMfa,
        ])->save();

        return $role;
    }

    private function upsertUser(string $email, string $name, string $employeeNumber, bool $mfaEnabled): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->uuid = (string) Str::uuid();
        }
        $user->fill([
            'name' => $name,
            'employee_number' => $employeeNumber,
            'password' => Hash::make('Password1!'),
            'is_active' => true,
            'mfa_enabled' => $mfaEnabled,
            'password_changed_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
