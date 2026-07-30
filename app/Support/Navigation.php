<?php

namespace App\Support;

class Navigation
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return config('navigation.sections', []);
    }

    /**
     * Flatten all navigable module items (excluding dashboard route-only if needed).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function modulesByKey(): array
    {
        $map = [];

        foreach (self::sections() as $section) {
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
