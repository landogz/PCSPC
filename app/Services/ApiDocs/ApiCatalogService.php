<?php

namespace App\Services\ApiDocs;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class ApiCatalogService
{
    /**
     * Build a public API catalog from registered /api/v1 routes.
     *
     * @return array{
     *   title: string,
     *   subtitle: string,
     *   base_path: string,
     *   updated_note: string,
     *   generated_at: string,
     *   conventions: array<string, string>,
     *   totals: array{endpoints: int, groups: int},
     *   groups: list<array<string, mixed>>
     * }
     */
    public function catalog(): array
    {
        $config = config('api_docs', []);
        $groupMeta = is_array($config['groups'] ?? null) ? $config['groups'] : [];
        $endpointMeta = is_array($config['endpoints'] ?? null) ? $config['endpoints'] : [];

        /** @var array<string, list<array<string, mixed>>> $buckets */
        $buckets = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $route instanceof Route) {
                continue;
            }

            $uri = ltrim($route->uri(), '/');
            if (! str_starts_with($uri, 'api/v1')) {
                continue;
            }

            $methods = array_values(array_filter(
                $route->methods(),
                static fn (string $method): bool => ! in_array($method, ['HEAD', 'OPTIONS'], true)
            ));
            if ($methods === []) {
                continue;
            }

            $groupKey = $this->groupKey($uri);
            foreach ($methods as $method) {
                $key = strtoupper($method).' '.$uri;
                $buckets[$groupKey][] = [
                    'method' => strtoupper($method),
                    'path' => '/'.$uri,
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'action' => $this->actionLabel($route),
                    'auth' => $this->requiresAuth($route),
                    'permissions' => $this->permissions($route),
                    'throttle' => $this->throttle($route),
                    'summary' => (string) ($endpointMeta[$key]['summary'] ?? $this->defaultSummary($method, $uri)),
                ];
            }
        }

        $groups = [];
        foreach ($buckets as $key => $endpoints) {
            usort($endpoints, static function (array $a, array $b): int {
                $pathCmp = strcmp($a['path'], $b['path']);
                if ($pathCmp !== 0) {
                    return $pathCmp;
                }

                return strcmp($a['method'], $b['method']);
            });

            $meta = $groupMeta[$key] ?? [];
            $groups[] = [
                'key' => $key,
                'label' => (string) ($meta['label'] ?? $this->headline($key)),
                'description' => (string) ($meta['description'] ?? ''),
                'order' => (int) ($meta['order'] ?? 999),
                'endpoints' => $endpoints,
                'count' => count($endpoints),
            ];
        }

        usort($groups, static function (array $a, array $b): int {
            if ($a['order'] === $b['order']) {
                return strcasecmp($a['label'], $b['label']);
            }

            return $a['order'] <=> $b['order'];
        });

        $endpointCount = array_sum(array_column($groups, 'count'));

        return [
            'title' => (string) ($config['title'] ?? 'API Reference'),
            'subtitle' => (string) ($config['subtitle'] ?? ''),
            'base_path' => (string) ($config['base_path'] ?? '/api/v1'),
            'updated_note' => (string) ($config['updated_note'] ?? ''),
            'generated_at' => now()->toIso8601String(),
            'conventions' => is_array($config['conventions'] ?? null) ? $config['conventions'] : [],
            'totals' => [
                'endpoints' => $endpointCount,
                'groups' => count($groups),
            ],
            'groups' => $groups,
        ];
    }

    private function groupKey(string $uri): string
    {
        $parts = explode('/', $uri);
        // api / v1 / {group}
        return $parts[2] ?? 'other';
    }

    private function actionLabel(Route $route): string
    {
        $action = $route->getActionName();
        if ($action === 'Closure') {
            return 'Closure';
        }

        if (str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);

            return class_basename($class).'@'.$method;
        }

        return class_basename($action);
    }

    private function requiresAuth(Route $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            $name = is_string($middleware) ? $middleware : '';
            if ($name === 'auth' || str_starts_with($name, 'auth:') || $name === 'auth:sanctum') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function permissions(Route $route): array
    {
        $permissions = [];
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                continue;
            }
            $value = substr($middleware, strlen('permission:'));
            foreach (explode('|', $value) as $permission) {
                $permission = trim($permission);
                if ($permission !== '') {
                    $permissions[] = $permission;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    private function throttle(Route $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'throttle:')) {
                return substr($middleware, strlen('throttle:'));
            }
        }

        return null;
    }

    private function defaultSummary(string $method, string $uri): string
    {
        $leaf = basename(preg_replace('#\{[^/]+\}#', '', $uri) ?? $uri);
        $leaf = trim($leaf, '/');
        $method = strtoupper($method);

        return match (true) {
            $leaf === 'meta' => 'Dropdown / form meta options.',
            $leaf === 'search' => 'Search records.',
            $leaf === 'export' => 'Export records.',
            $leaf === 'stats' => 'Aggregate statistics.',
            $leaf === 'print' => 'Printable report payload.',
            $leaf === 'login' => 'Authenticate and start a session or issue a token.',
            $leaf === 'logout' => 'End the current session / revoke token.',
            $leaf === 'logout-others' => 'Revoke other device sessions.',
            $leaf === 'me' => 'Current authenticated user.',
            $leaf === 'password' && $method === 'POST' => 'Change password.',
            $leaf === 'policy' || str_ends_with($uri, 'password/policy') => 'Password policy.',
            $leaf === 'unlock' => 'Unlock a locked account.',
            $leaf === 'deactivate' => 'Deactivate a record.',
            $leaf === 'logo' && $method === 'POST' => 'Upload company logo.',
            $leaf === 'logo' && $method === 'DELETE' => 'Remove company logo.',
            $leaf === 'bulk-delete' => 'Bulk delete records.',
            $leaf === 'bulk-category' => 'Bulk update category.',
            $leaf === 'types' => 'List lookup types.',
            $leaf === 'options' => 'Active options for forms.',
            $leaf === 'events' => 'List distinct audit event names.',
            $leaf === 'roles' && $method === 'GET' => 'List roles.',
            $leaf === 'permissions' => 'List permissions.',
            $leaf === 'up' || $leaf === 'health' => 'Health check.',
            $method === 'GET' && ! str_contains($uri, '{') => 'List records.',
            $method === 'GET' && str_contains($uri, '{') => 'Retrieve a single record.',
            $method === 'POST' => 'Create a record.',
            in_array($method, ['PUT', 'PATCH'], true) => 'Update a record.',
            $method === 'DELETE' => 'Delete a record.',
            default => 'API endpoint.',
        };
    }

    private function headline(string $key): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $key));
    }
}
