<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfilePhoto
{
    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        // Root-relative so avatars work regardless of APP_URL host/port
        // (e.g. artisan serve on :8002 while APP_URL still says :8000).
        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    public static function forPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return self::url($path);
    }

    public static function forEmployee(?Employee $employee): ?string
    {
        if ($employee === null) {
            return null;
        }

        return self::forPath($employee->photo_path);
    }

    public static function forUser(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $fromAvatar = self::forPath($user->avatar_path);
        if ($fromAvatar !== null) {
            return $fromAvatar;
        }

        if ($user->relationLoaded('employee')) {
            return self::forEmployee($user->employee);
        }

        return self::forEmployee($user->employee()->first());
    }
}
