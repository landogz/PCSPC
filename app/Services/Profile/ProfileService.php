<?php

namespace App\Services\Profile;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Employees\EmployeeRepository;
use App\Repositories\Profile\ProfileRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function __construct(
        private readonly ProfileRepository $profiles,
        private readonly EmployeeRepository $employees,
        private readonly AuditLogger $audit,
    ) {}

    public function show(User $user): User
    {
        return $this->profiles->findForAuth($user);
    }

    /**
     * @param  array{name: string}  $payload
     */
    public function update(User $user, array $payload): User
    {
        $updated = $this->profiles->update($user, [
            'name' => $payload['name'],
        ]);

        if ($updated->employee !== null) {
            $parts = $this->splitName($payload['name']);
            $this->employees->update($updated->employee, array_filter([
                'first_name' => $parts['first_name'],
                'middle_name' => $parts['middle_name'],
                'last_name' => $parts['last_name'],
            ], static fn ($value): bool => $value !== null && $value !== ''));
        }

        $this->audit->log('profile.updated', [
            'user_id' => $updated->uuid,
            'name' => $updated->name,
        ]);

        return $this->profiles->findForAuth($updated);
    }

    public function uploadAvatar(User $user, UploadedFile $photo): User
    {
        return DB::transaction(function () use ($user, $photo): User {
            $this->deleteStoredPhoto($user->avatar_path);

            $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
            $path = $photo->storeAs(
                'users/avatars',
                $user->uuid.'.'.$extension,
                'public'
            );

            $updated = $this->profiles->update($user, ['avatar_path' => $path]);

            if ($updated->employee instanceof Employee) {
                $this->syncEmployeePhoto($updated->employee, $path);
            }

            $this->audit->log('profile.avatar_updated', [
                'user_id' => $updated->uuid,
            ]);

            return $this->profiles->findForAuth($updated);
        });
    }

    public function removeAvatar(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $this->deleteStoredPhoto($user->avatar_path);

            $updated = $this->profiles->update($user, ['avatar_path' => null]);

            if ($updated->employee instanceof Employee && filled($updated->employee->photo_path)) {
                $this->deleteStoredPhoto($updated->employee->photo_path);
                $this->employees->update($updated->employee, ['photo_path' => null]);
            }

            $this->audit->log('profile.avatar_removed', [
                'user_id' => $updated->uuid,
            ]);

            return $this->profiles->findForAuth($updated);
        });
    }

    private function syncEmployeePhoto(Employee $employee, string $path): void
    {
        if ($employee->photo_path && $employee->photo_path !== $path) {
            $this->deleteStoredPhoto($employee->photo_path);
        }

        $this->employees->update($employee, ['photo_path' => $path]);
    }

    private function deleteStoredPhoto(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array{first_name: string, middle_name: ?string, last_name: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, static fn ($part): bool => $part !== ''));

        if ($parts === []) {
            return [
                'first_name' => 'User',
                'middle_name' => null,
                'last_name' => 'Account',
            ];
        }

        if (count($parts) === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[0],
            ];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);

        return [
            'first_name' => (string) $first,
            'middle_name' => $parts === [] ? null : implode(' ', $parts),
            'last_name' => (string) $last,
        ];
    }
}
