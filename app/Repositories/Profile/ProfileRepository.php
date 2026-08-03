<?php

namespace App\Repositories\Profile;

use App\Models\User;

class ProfileRepository
{
    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user->fresh(['roles.permissions', 'employee']) ?? $user;
    }

    public function findForAuth(User $user): User
    {
        return $user->loadMissing(['roles.permissions', 'employee']);
    }
}
