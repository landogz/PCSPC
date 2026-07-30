<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Employees\EmployeeRepository;
use App\Services\Administration\PasswordPolicyService;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $audit,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * @param  array{search?: string, status?: string, department?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->employees->paginate($filters, $perPage);
    }

    public function find(string $uuid): Employee
    {
        $employee = $this->employees->findByUuid($uuid);

        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{employee: Employee, temporary_password: string|null}
     */
    public function create(array $payload, ?UploadedFile $photo = null): array
    {
        $result = DB::transaction(function () use ($payload, $photo): array {
            $data = $this->mapPayload($payload);
            $employee = $this->employees->create($data);

            $provision = $this->provisionUser($employee, true);
            $employee = $this->employees->update($employee, ['user_id' => $provision['user']->id]);

            if ($photo !== null) {
                $employee = $this->storePhoto($employee, $photo);
            }

            return [
                'employee' => $employee->fresh(['department', 'user.roles']),
                'temporary_password' => $provision['temporary_password'],
            ];
        });

        $employee = $result['employee'];
        $this->audit->log('employee.created', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->fullName(),
            'account_provisioned' => $result['temporary_password'] !== null || $employee->user_id !== null,
            'has_photo' => filled($employee->photo_path),
        ]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{employee: Employee, temporary_password: string|null}
     */
    public function update(string $uuid, array $payload, ?UploadedFile $photo = null, bool $removePhoto = false): array
    {
        $result = DB::transaction(function () use ($uuid, $payload, $photo, $removePhoto): array {
            $employee = $this->find($uuid);
            $data = $this->mapPayload($payload, $employee);
            $employee = $this->employees->update($employee, $data);

            $provision = $this->provisionUser($employee, false);
            if ($employee->user_id !== $provision['user']->id) {
                $employee = $this->employees->update($employee, ['user_id' => $provision['user']->id]);
            } else {
                $employee = $employee->fresh(['department', 'user.roles']);
            }

            $this->syncUserActiveState($employee);

            if ($removePhoto) {
                $employee = $this->clearPhoto($employee);
            } elseif ($photo !== null) {
                $employee = $this->storePhoto($employee, $photo);
            }

            return [
                'employee' => $employee->fresh(['department', 'user.roles']),
                'temporary_password' => $provision['temporary_password'],
            ];
        });

        $employee = $result['employee'];
        $this->audit->log('employee.updated', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->fullName(),
            'employment_status' => $employee->employment_status,
            'password_reset' => $result['temporary_password'] !== null,
            'has_photo' => filled($employee->photo_path),
        ]);

        return $result;
    }

    public function deactivate(string $uuid): Employee
    {
        $employee = DB::transaction(function () use ($uuid): Employee {
            $employee = $this->find($uuid);
            $employee = $this->employees->update($employee, [
                'employment_status' => 'inactive',
            ]);
            $this->syncUserActiveState($employee);

            return $employee;
        });

        $this->audit->log('employee.deactivated', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->fullName(),
        ]);

        return $employee;
    }

    public function delete(string $uuid): void
    {
        $employee = $this->find($uuid);

        if ($employee->user_id) {
            throw ValidationException::withMessages([
                'employee' => ['Unlink or deactivate the employee account before deleting the 201 record.'],
            ]);
        }

        $meta = [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->fullName(),
        ];

        $this->clearPhoto($employee);
        $this->employees->delete($employee);

        $this->audit->log('employee.deleted', $meta);
    }

    public function storePhoto(Employee $employee, UploadedFile $photo): Employee
    {
        $this->deleteStoredPhoto($employee->photo_path);

        $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
        $path = $photo->storeAs(
            'employees/photos',
            $employee->uuid.'.'.$extension,
            'public'
        );

        $updated = $this->employees->update($employee, ['photo_path' => $path]);

        $this->audit->log('employee.photo_updated', [
            'employee_id' => $updated->uuid,
            'employee_number' => $updated->employee_number,
        ]);

        return $updated;
    }

    public function clearPhoto(Employee $employee): Employee
    {
        if (! filled($employee->photo_path)) {
            return $employee;
        }

        $this->deleteStoredPhoto($employee->photo_path);

        return $this->employees->update($employee, ['photo_path' => null]);
    }

    private function deleteStoredPhoto(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * @return array{departments: list<array<string, mixed>>, statuses: list<string>}
     */
    public function meta(): array
    {
        return [
            'departments' => $this->employees->activeDepartments()->map(fn ($department) => [
                'id' => $department->uuid,
                'code' => $department->code,
                'name' => $department->name,
            ])->values()->all(),
            'statuses' => Employee::STATUSES,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mapPayload(array $payload, ?Employee $existing = null): array
    {
        $departmentUuid = $payload['department_id'] ?? null;

        return [
            'employee_number' => strtoupper(trim((string) $payload['employee_number'])),
            'first_name' => trim((string) $payload['first_name']),
            'middle_name' => $this->nullableString($payload['middle_name'] ?? null),
            'last_name' => trim((string) $payload['last_name']),
            'suffix' => $this->nullableString($payload['suffix'] ?? null),
            'email' => $this->nullableEmail($payload['email'] ?? null),
            'mobile' => $this->nullableString($payload['mobile'] ?? null),
            'department_id' => $this->employees->departmentIdByUuid(is_string($departmentUuid) ? $departmentUuid : null),
            'position_title' => $this->nullableString($payload['position_title'] ?? null),
            'employment_status' => $payload['employment_status'] ?? $existing?->employment_status ?? 'active',
            'date_hired' => $payload['date_hired'] ?? null,
            'date_regularized' => $payload['date_regularized'] ?? null,
            'date_separated' => $payload['date_separated'] ?? null,
            'birth_date' => $payload['birth_date'] ?? null,
            'gender' => $this->nullableString($payload['gender'] ?? null),
            'civil_status' => $this->nullableString($payload['civil_status'] ?? null),
            'nationality' => $this->nullableString($payload['nationality'] ?? null),
            'address_line' => $this->nullableString($payload['address_line'] ?? null),
            'city' => $this->nullableString($payload['city'] ?? null),
            'province' => $this->nullableString($payload['province'] ?? null),
            'zip_code' => $this->nullableString($payload['zip_code'] ?? null),
            'tin' => $this->nullableString($payload['tin'] ?? null),
            'sss_number' => $this->nullableString($payload['sss_number'] ?? null),
            'philhealth_number' => $this->nullableString($payload['philhealth_number'] ?? null),
            'pagibig_number' => $this->nullableString($payload['pagibig_number'] ?? null),
        ];
    }

    /**
     * @return array{user: User, temporary_password: string|null}
     */
    private function provisionUser(Employee $employee, bool $allowCreatePassword): array
    {
        $email = $employee->email;
        if (! filled($email)) {
            throw ValidationException::withMessages([
                'email' => ['Email is required to provision an employee login.'],
            ]);
        }

        $role = $this->employees->findEmployeeRole();
        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => ['Employee role is missing. Seed roles before creating employees.'],
            ]);
        }

        $temporaryPassword = null;
        $user = $employee->user_id
            ? User::query()->find($employee->user_id)
            : $this->employees->findUserByEmployeeNumberOrEmail($employee->employee_number, $email);

        if ($user !== null) {
            if ($user->isProtected()) {
                throw ValidationException::withMessages([
                    'email' => ['This protected login cannot be linked to an employee 201 record.'],
                ]);
            }

            $alreadyLinked = Employee::query()
                ->where('user_id', $user->id)
                ->when($employee->exists, fn ($query) => $query->where('id', '!=', $employee->id))
                ->exists();

            if ($alreadyLinked) {
                throw ValidationException::withMessages([
                    'email' => ['This login is already linked to another employee record.'],
                ]);
            }
        }

        if ($user === null) {
            $temporaryPassword = $allowCreatePassword ? $this->generateTemporaryPassword() : null;
            if ($temporaryPassword === null) {
                throw ValidationException::withMessages([
                    'user' => ['Unable to create login for this employee.'],
                ]);
            }

            $user = User::query()->create([
                'name' => $employee->fullName(),
                'email' => $email,
                'employee_number' => $employee->employee_number,
                'password' => $temporaryPassword,
                'is_active' => $employee->isActiveEmployment(),
                'mfa_enabled' => false,
                'password_changed_at' => now(),
                'must_change_password' => $this->passwordPolicy->current()['force_change_temporary'],
                'email_verified_at' => now(),
            ]);
        } else {
            $user->fill([
                'name' => $employee->fullName(),
                'email' => $email,
                'employee_number' => $employee->employee_number,
                'is_active' => $employee->isActiveEmployment(),
            ])->save();
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return [
            'user' => $user->fresh(['roles']),
            'temporary_password' => $temporaryPassword,
        ];
    }

    private function syncUserActiveState(Employee $employee): void
    {
        if (! $employee->user_id) {
            return;
        }

        User::query()->whereKey($employee->user_id)->update([
            'is_active' => $employee->isActiveEmployment(),
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        return 'Tmp!'.Str::password(10, symbols: false);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableEmail(mixed $value): ?string
    {
        $email = $this->nullableString($value);

        return $email ? strtolower($email) : null;
    }
}
