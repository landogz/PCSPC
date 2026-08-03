<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeCareerHistory;
use App\Models\EmployeeDependent;
use App\Models\EmployeeEducation;
use App\Models\User;
use App\Mail\Employees\EmployeeWelcomeMail;
use App\Repositories\Employees\EmployeeRepository;
use App\Services\Administration\PasswordPolicyService;
use App\Services\Audit\AuditLogger;
use App\Services\Exports\XlsxWriter;
use App\Services\Lookups\LookupService;
use App\Services\Notifications\NotificationService;
use App\Support\ProfilePhoto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $audit,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly XlsxWriter $xlsx,
        private readonly LookupService $lookups,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array{search?: string, status?: string, department?: string}  $filters
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->employees->paginate($filters, $perPage);
    }

    /**
     * Shared employee typeahead payload (Documents, Security, etc.).
     *
     * @return list<array<string, mixed>>
     */
    public function searchLookup(string $search, int $limit = 15): array
    {
        return $this->employees->searchLookup($search, $limit)->map(function (Employee $employee): array {
            $hasAccount = $employee->user_id !== null
                || $this->employees->findUserByEmployeeNumberOrEmail(
                    $employee->employee_number,
                    $employee->email,
                ) !== null;

            return [
                'id' => $employee->uuid,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->fullName(),
                'email' => $employee->email,
                'photo_url' => $employee->photoUrl() ?? ProfilePhoto::forUser($employee->user),
                'has_account' => $hasAccount,
                'label' => trim($employee->employee_number.' — '.$employee->fullName()),
            ];
        })->values()->all();
    }

    /**
     * Build an Excel (.xlsx) export for the current employee filters.
     *
     * @param  array{search?: string, status?: string, department?: string}  $filters
     * @return array{binary: string, filename: string, row_count: int, truncated: bool}
     */
    public function export(array $filters = [], bool $revealStatutory = false): array
    {
        $limit = 5000;
        $employees = $this->employees->forExport($filters, $limit + 1);
        $truncated = $employees->count() > $limit;
        if ($truncated) {
            $employees = $employees->take($limit);
        }

        $headers = [
            'Employee Number',
            'First Name',
            'Middle Name',
            'Last Name',
            'Suffix',
            'Full Name',
            'Email',
            'Mobile',
            'Department Code',
            'Department',
            'Position',
            'Employment Status',
            'Date Hired',
            'Date Regularized',
            'Date Separated',
            'Birth Date',
            'Gender',
            'Civil Status',
            'Nationality',
            'Address',
            'City',
            'Province',
            'ZIP Code',
            'TIN',
            'SSS Number',
            'PhilHealth Number',
            'Pag-IBIG Number',
            'Linked Login Email',
            'Login Active',
        ];

        $rows = $employees->map(function (Employee $employee) use ($revealStatutory): array {
            return [
                $employee->employee_number,
                $employee->first_name,
                $employee->middle_name,
                $employee->last_name,
                $employee->suffix,
                $employee->fullName(),
                $employee->email,
                $employee->mobile,
                $employee->department?->code,
                $employee->department?->name,
                $employee->position_title,
                $employee->employment_status,
                $employee->date_hired?->toDateString(),
                $employee->date_regularized?->toDateString(),
                $employee->date_separated?->toDateString(),
                $employee->birth_date?->toDateString(),
                $employee->gender,
                $employee->civil_status,
                $employee->nationality,
                $employee->address_line,
                $employee->city,
                $employee->province,
                $employee->zip_code,
                $this->maskOrRevealStatutory($employee->tin, $revealStatutory),
                $this->maskOrRevealStatutory($employee->sss_number, $revealStatutory),
                $this->maskOrRevealStatutory($employee->philhealth_number, $revealStatutory),
                $this->maskOrRevealStatutory($employee->pagibig_number, $revealStatutory),
                $employee->user?->email,
                $employee->user === null ? null : ($employee->user->is_active ? 'Yes' : 'No'),
            ];
        })->values()->all();

        $binary = $this->xlsx->toString($headers, $rows, 'Employees');
        $filename = 'employees-'.now()->format('Y-m-d-His').'.xlsx';

        $this->audit->log('employee.exported', [
            'row_count' => count($rows),
            'truncated' => $truncated,
            'statutory_revealed' => $revealStatutory,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'department' => (string) ($filters['department'] ?? ''),
            ],
        ]);

        return [
            'binary' => $binary,
            'filename' => $filename,
            'row_count' => count($rows),
            'truncated' => $truncated,
        ];
    }

    private function maskOrRevealStatutory(?string $value, bool $reveal): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($reveal) {
            return $value;
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', max(0, $length - 4)).substr($value, -4);
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
     * @return array{employee: Employee, temporary_password: string|null, welcome_email_sent: bool}
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
                'user' => $provision['user'],
            ];
        });

        $employee = $result['employee'];
        $welcomeEmailSent = false;

        if (filled($result['temporary_password']) && $result['user'] instanceof User) {
            $welcomeEmailSent = $this->sendWelcomeEmail(
                $result['user'],
                (string) $result['temporary_password'],
                (string) $employee->employee_number,
            );
        }

        $this->audit->log('employee.created', [
            'employee_id' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->fullName(),
            'account_provisioned' => $result['temporary_password'] !== null || $employee->user_id !== null,
            'welcome_email_sent' => $welcomeEmailSent,
            'has_photo' => filled($employee->photo_path),
        ]);

        return [
            'employee' => $employee,
            'temporary_password' => $result['temporary_password'],
            'welcome_email_sent' => $welcomeEmailSent,
        ];
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
        $employee->loadMissing('user');
        $previousEmployeePhoto = $employee->photo_path;
        $previousUserAvatar = $employee->user?->avatar_path;

        $this->deleteStoredPhoto($previousEmployeePhoto);
        if (filled($previousUserAvatar) && $previousUserAvatar !== $previousEmployeePhoto) {
            $this->deleteStoredPhoto($previousUserAvatar);
        }

        $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
        $path = $photo->storeAs(
            'employees/photos',
            $employee->uuid.'.'.$extension,
            'public'
        );

        $updated = $this->employees->update($employee, ['photo_path' => $path]);

        if ($updated->user !== null) {
            $updated->user->forceFill(['avatar_path' => $path])->save();
        }

        $this->audit->log('employee.photo_updated', [
            'employee_id' => $updated->uuid,
            'employee_number' => $updated->employee_number,
        ]);

        return $updated;
    }

    public function clearPhoto(Employee $employee): Employee
    {
        $employee->loadMissing('user');

        if (! filled($employee->photo_path) && ! filled($employee->user?->avatar_path)) {
            return $employee;
        }

        $this->deleteStoredPhoto($employee->photo_path);
        if (filled($employee->user?->avatar_path) && $employee->user->avatar_path !== $employee->photo_path) {
            $this->deleteStoredPhoto($employee->user->avatar_path);
        }

        if ($employee->user !== null) {
            $employee->user->forceFill(['avatar_path' => null])->save();
        }

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
     * @return array{
     *   departments: list<array<string, mixed>>,
     *   statuses: list<string>,
     *   status_options: list<array{code: string, label: string}>,
     *   genders: list<array{code: string, label: string}>,
     *   civil_statuses: list<array{code: string, label: string}>,
     *   dependent_relationships: list<string>,
     *   dependent_relationship_options: list<array{code: string, label: string}>,
     *   education_levels: list<string>,
     *   education_level_options: list<array{code: string, label: string}>,
     *   employment_categories: list<string>,
     *   employment_category_options: list<array{code: string, label: string}>,
     *   salary_rate_types: list<string>
     * }
     */
    public function meta(): array
    {
        $statusOptions = $this->lookups->activeOptions('employment_status', Employee::STATUSES);
        $relationshipOptions = $this->lookups->activeOptions('dependent_relationship', EmployeeDependent::RELATIONSHIPS);
        $educationOptions = $this->lookups->activeOptions('education_level', EmployeeEducation::LEVELS);
        $categoryOptions = $this->lookups->activeOptions('employment_category', EmployeeCareerHistory::CATEGORIES);

        return [
            'departments' => $this->employees->activeDepartments()->map(fn ($department) => [
                'id' => $department->uuid,
                'code' => $department->code,
                'name' => $department->name,
            ])->values()->all(),
            'statuses' => array_column($statusOptions, 'code'),
            'status_options' => $statusOptions,
            'genders' => $this->lookups->activeOptions('gender'),
            'civil_statuses' => $this->lookups->activeOptions('civil_status'),
            'dependent_relationships' => array_column($relationshipOptions, 'code'),
            'dependent_relationship_options' => $relationshipOptions,
            'education_levels' => array_column($educationOptions, 'code'),
            'education_level_options' => $educationOptions,
            'employment_categories' => array_column($categoryOptions, 'code'),
            'employment_category_options' => $categoryOptions,
            'salary_rate_types' => EmployeeCareerHistory::RATE_TYPES,
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

    private function sendWelcomeEmail(User $user, string $temporaryPassword, string $employeeNumber): bool
    {
        if (! filled($user->email)) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new EmployeeWelcomeMail(
                user: $user,
                temporaryPassword: $temporaryPassword,
                employeeNumber: $employeeNumber,
            ));

            $this->notifications->notify(
                user: $user,
                type: 'employee.welcome',
                title: 'Welcome to '.config('app.name'),
                body: 'Your account is ready. Check your email for temporary login credentials, then sign in and change your password.',
                actionUrl: url('/login'),
                meta: [
                    'employee_number' => $employeeNumber,
                    'email' => $user->email,
                ],
            );

            $this->audit->log('employee.welcome_email_sent', [
                'user_id' => $user->uuid,
                'employee_number' => $employeeNumber,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to send employee welcome email.', [
                'user_id' => $user->uuid,
                'employee_number' => $employeeNumber,
                'error' => $exception->getMessage(),
            ]);

            $this->audit->log('employee.welcome_email_failed', [
                'user_id' => $user->uuid,
                'employee_number' => $employeeNumber,
                'email' => $user->email,
            ]);

            return false;
        }
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
