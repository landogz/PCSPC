<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
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
            ['name' => 'Manage Departments', 'slug' => 'departments.manage', 'module' => 'departments'],
            ['name' => 'View Employees', 'slug' => 'employees.view', 'module' => 'employee'],
            ['name' => 'Manage Employees', 'slug' => 'employees.manage', 'module' => 'employee'],
            ['name' => 'View Documents', 'slug' => 'documents.view', 'module' => 'documents'],
            ['name' => 'Manage Documents', 'slug' => 'documents.manage', 'module' => 'documents'],
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
                'departments.manage',
                'employees.view',
                'employees.manage',
                'documents.view',
                'documents.manage',
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
        $admin->roles()->sync([$hrAdmin->id]);

        $demoEmployee = $this->upsertUser(
            'employee@pcspc.local',
            'Demo Employee',
            'EMP-1001',
            false,
        );
        $demoEmployee->roles()->sync([$employee->id]);

        $mfaEmail = (string) env('MFA_DEMO_EMAIL', 'mfa@pcspc.local');
        $mfaUser = $this->upsertUser($mfaEmail, 'MFA Demo Admin', 'EMP-0002', true);
        $mfaUser->roles()->sync([$superAdmin->id]);

        // Platform super-admin: not linked to an employee 201 record; cannot be deleted/deactivated.
        $platformSuperAdmin = $this->upsertUser(
            'superadmin@pcspc.local',
            'Platform Super Admin',
            null,
            false,
            true,
        );
        $platformSuperAdmin->roles()->sync([$superAdmin->id]);

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

        $hrDepartmentId = Department::query()->where('code', 'HR')->value('id');
        $itDepartmentId = Department::query()->where('code', 'IT')->value('id');

        $this->upsertEmployee($admin, [
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'department_id' => $hrDepartmentId,
            'position_title' => 'HR Administrator',
            'employment_status' => 'active',
            'date_hired' => '2024-01-15',
            'tin' => '123-456-789-000',
            'sss_number' => '34-1234567-8',
            'philhealth_number' => '12-345678901-2',
            'pagibig_number' => '1212-3456-7890',
        ]);

        $this->upsertEmployee($demoEmployee, [
            'first_name' => 'Demo',
            'last_name' => 'Employee',
            'department_id' => $itDepartmentId,
            'position_title' => 'Staff',
            'employment_status' => 'active',
            'date_hired' => '2024-06-01',
            'tin' => '987-654-321-000',
            'sss_number' => '34-7654321-0',
            'philhealth_number' => '12-987654321-0',
            'pagibig_number' => '1212-9876-5432',
        ]);

        $this->upsertEmployee($mfaUser, [
            'first_name' => 'MFA',
            'middle_name' => 'Demo',
            'last_name' => 'Admin',
            'department_id' => $itDepartmentId,
            'position_title' => 'Super Admin',
            'employment_status' => 'active',
            'date_hired' => '2023-11-01',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertEmployee(User $user, array $attributes): Employee
    {
        $employee = Employee::query()->firstOrNew(['employee_number' => $user->employee_number]);
        if (! $employee->exists) {
            $employee->uuid = (string) Str::uuid();
        }

        $nameParts = preg_split('/\s+/', trim($user->name)) ?: [];
        $firstName = $attributes['first_name'] ?? ($nameParts[0] ?? 'Employee');
        $lastName = $attributes['last_name'] ?? (end($nameParts) ?: 'User');

        $employee->fill([
            ...$attributes,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'user_id' => $user->id,
            'employment_status' => $attributes['employment_status'] ?? 'active',
            'nationality' => $attributes['nationality'] ?? 'Filipino',
        ])->save();

        return $employee;
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

    private function upsertUser(
        string $email,
        string $name,
        ?string $employeeNumber,
        bool $mfaEnabled,
        bool $isProtected = false,
    ): User {
        $user = User::query()->where('email', $email)->first();

        if ($user === null && filled($employeeNumber)) {
            $user = User::query()->where('employee_number', $employeeNumber)->first();
        }

        if ($user === null) {
            $user = new User([
                'uuid' => (string) Str::uuid(),
                'email' => $email,
            ]);
        }

        $user->fill([
            'email' => $email,
            'name' => $name,
            'employee_number' => $employeeNumber,
            'password' => Hash::make('Password1!'),
            'is_active' => true,
            'is_protected' => $isProtected,
            'mfa_enabled' => $mfaEnabled,
            'password_changed_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}