<?php

namespace App\Services\Search;

use App\Models\User;
use App\Services\Employees\EmployeeService;
use App\Support\Navigation;

class SearchService
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {}

    /**
     * Global topbar mega-search payload for the authenticated user.
     *
     * @return array{
     *     query: string,
     *     modules: list<array<string, mixed>>,
     *     people: list<array<string, mixed>>,
     *     shortcuts: list<array<string, mixed>>,
     *     sections: list<array{label: string, items: list<array<string, mixed>>}>
     * }
     */
    public function search(User $user, string $query = '', int $peopleLimit = 8): array
    {
        $query = trim($query);
        $needle = mb_strtolower($query);

        $sections = [];
        $modules = [];

        foreach (Navigation::sections($user) as $section) {
            $items = [];
            foreach ($section['items'] ?? [] as $item) {
                $entry = $this->moduleEntry($item, (string) ($section['label'] ?? ''));
                if ($needle !== '' && ! $this->matches($needle, [
                    $entry['label'],
                    $entry['section'],
                    $entry['summary'] ?? '',
                    $entry['key'],
                ])) {
                    continue;
                }
                $items[] = $entry;
                $modules[] = $entry;
            }
            if ($items !== []) {
                $sections[] = [
                    'label' => (string) ($section['label'] ?? 'Modules'),
                    'items' => $items,
                ];
            }
        }

        $people = [];
        if ($user->hasPermission('employees.view')) {
            $people = $this->employees->searchLookup($query, $peopleLimit);
            foreach ($people as &$person) {
                $person['url'] = route('modules.show', ['module' => 'employees'])
                    .'?highlight='.urlencode((string) ($person['id'] ?? ''));
                $person['kind'] = 'person';
            }
            unset($person);
        }

        $shortcuts = array_values(array_filter(
            $this->shortcuts($user),
            fn (array $item): bool => $needle === '' || $this->matches($needle, [
                $item['label'],
                $item['description'] ?? '',
            ]),
        ));

        return [
            'query' => $query,
            'modules' => $modules,
            'people' => $people,
            'shortcuts' => $shortcuts,
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function moduleEntry(array $item, string $section): array
    {
        return [
            'kind' => 'module',
            'key' => (string) ($item['key'] ?? ''),
            'label' => (string) ($item['label'] ?? ''),
            'icon' => (string) ($item['icon'] ?? 'ph-squares-four'),
            'section' => $section,
            'summary' => (string) ($item['summary'] ?? ''),
            'phase' => (string) ($item['phase'] ?? ''),
            'url' => Navigation::href($item),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shortcuts(User $user): array
    {
        $items = [
            [
                'kind' => 'shortcut',
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'description' => 'Go to your home dashboard',
                'icon' => 'ph-squares-four',
                'url' => route('dashboard'),
            ],
            [
                'kind' => 'shortcut',
                'key' => 'notifications',
                'label' => 'Notifications',
                'description' => 'Open your in-app inbox',
                'icon' => 'ph-bell-ringing',
                'url' => route('modules.show', ['module' => 'notifications']),
            ],
            [
                'kind' => 'shortcut',
                'key' => 'edit-profile',
                'label' => 'Edit profile',
                'description' => 'Update your name and photo',
                'icon' => 'ph-user-circle',
                'url' => '#edit-profile',
                'action' => 'edit-profile',
            ],
            [
                'kind' => 'shortcut',
                'key' => 'change-password',
                'label' => 'Change password',
                'description' => 'Update your account password',
                'icon' => 'ph-lock-key',
                'url' => '#change-password',
                'action' => 'change-password',
            ],
        ];

        if (! Navigation::userCanAccess($user, Navigation::find('notifications') ?? [])) {
            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => ($item['key'] ?? '') !== 'notifications',
            ));
        }

        return $items;
    }

    /**
     * @param  list<string>  $haystacks
     */
    private function matches(string $needle, array $haystacks): bool
    {
        foreach ($haystacks as $value) {
            if ($value !== '' && str_contains(mb_strtolower($value), $needle)) {
                return true;
            }
        }

        return false;
    }
}
