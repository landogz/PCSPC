<?php

namespace App\Repositories\Administration;

use App\Models\SystemSetting;

class SystemSettingRepository
{
    public function get(string $key): mixed
    {
        $setting = SystemSetting::query()->where('key', $key)->first();

        return $setting?->value;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function put(string $key, array $value): SystemSetting
    {
        return SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
