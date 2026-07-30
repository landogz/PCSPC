<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Navigation
{
    /**
     * All configured sections (unfiltered).
     *
     * @return list<array<string, mixed>>
     */
    public static function allSections(): array
    {
        return config('navigation.sections', []);
    }

    /**
     * Sidebar sections visible to the given (or current) user.
     *
     * @return list<array<string, mixed>>
     */
    public static function sections(?User $user = null): array
    {
        $user ??= Auth::user();

        if ($user === null) {
            return [];
        }

        $sections = [];

        foreach (self::allSections() as $section) {
            $items = [];

            foreach ($section['items'] ?? [] as $item) {
                if (self::userCanAccess($user, $item)) {
                    $items[] = $item;
                }
            }

            if ($items === []) {
                continue;
            }

            $sections[] = array_merge($section, ['items' => $items]);
        }

        return $sections;
    }

    /**
     * Flatten all navigable module items (excluding dashboard route-only if needed).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function modulesByKey(?User $user = null, bool $filtered = false): array
    {
        $map = [];
        $sections = $filtered ? self::sections($user) : self::allSections();

        foreach ($sections as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $key = $item['key'] ?? null;
                if (! is_string($key) || $key === '') {
                    continue;
                }
                $map[$key] = $item + ['section' => $section['label'] ?? ''];
            }
        }

        return $map;
    }

    public static function find(string $key): ?array
    {
        return self::modulesByKey()[$key] ?? null;
    }

    /**
     * Keys that use the modules.show route (exclude dashboard).
     *
     * @return list<string>
     */
    public static function moduleKeys(): array
    {
        return array_values(array_filter(
            array_keys(self::modulesByKey()),
            static fn (string $key): bool => $key !== 'dashboard'
        ));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function userCanAccess(?User $user, array $item): bool
    {
        if ($user === null) {
            return false;
        }

        $permission = $item['permission'] ?? null;

        if ($permission === null || $permission === '') {
            return false;
        }

        $required = is_array($permission) ? $permission : [$permission];

        foreach ($required as $slug) {
            if (is_string($slug) && $slug !== '' && $user->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    public static function href(array $item): string
    {
        $route = $item['route'] ?? 'modules.show';

        if ($route === 'dashboard') {
            return route('dashboard');
        }

        return route('modules.show', ['module' => $item['key']]);
    }

    public static function isActive(array $item): bool
    {
        $key = $item['key'] ?? null;

        if ($key === 'dashboard') {
            return request()->routeIs('dashboard');
        }

        return request()->routeIs('modules.show')
            && request()->route('module') === $key;
    }
}
