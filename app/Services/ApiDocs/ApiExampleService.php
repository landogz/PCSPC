<?php

namespace App\Services\ApiDocs;

class ApiExampleService
{
    private const SAMPLE_UUID = '11111111-2222-4333-8444-555555555555';

    private const SAMPLE_UUID_NESTED = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

    /**
     * @return list<array{id: string, label: string}>
     */
    public function languages(): array
    {
        return [
            ['id' => 'curl', 'label' => 'cURL'],
            ['id' => 'php', 'label' => 'PHP'],
            ['id' => 'python', 'label' => 'Python'],
            ['id' => 'java', 'label' => 'Java'],
            ['id' => 'javascript', 'label' => 'JavaScript'],
            ['id' => 'csharp', 'label' => 'C#'],
        ];
    }

    /**
     * Featured getting-started examples (subset of the live catalog).
     *
     * @return list<array{id: string, title: string, description: string, snippets: array<string, string>}>
     */
    public function featuredExamples(?string $baseUrl = null): array
    {
        $base = $this->baseUrl($baseUrl);

        $featured = [
            [
                'method' => 'POST',
                'path' => '/api/v1/auth/login',
                'uri' => 'api/v1/auth/login',
                'auth' => false,
                'summary' => 'Authenticate and start a session or issue a token.',
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/auth/me',
                'uri' => 'api/v1/auth/me',
                'auth' => true,
                'summary' => 'Current authenticated user profile and permissions.',
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/employees',
                'uri' => 'api/v1/employees',
                'auth' => true,
                'summary' => 'List employees.',
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/schedules/print',
                'uri' => 'api/v1/schedules/print',
                'auth' => true,
                'summary' => 'Landscape printable schedule report.',
            ],
        ];

        $out = [];
        foreach ($featured as $index => $endpoint) {
            $out[] = [
                'id' => 'featured-'.($index + 1),
                'title' => ($index + 1).'. '.$endpoint['method'].' '.$endpoint['path'],
                'description' => $endpoint['summary'],
                'snippets' => $this->snippetsFor($endpoint, $base),
            ];
        }

        return $out;
    }

    /**
     * Multi-language request snippets for a catalog endpoint.
     *
     * @param  array{method: string, path: string, uri?: string, auth?: bool, summary?: string}  $endpoint
     * @return array<string, string>
     */
    public function snippetsFor(array $endpoint, ?string $baseUrl = null): array
    {
        $base = $this->baseUrl($baseUrl);
        $method = strtoupper((string) ($endpoint['method'] ?? 'GET'));
        $path = $this->concretePath((string) ($endpoint['path'] ?? '/api/v1'));
        $url = $base.$path;
        $auth = (bool) ($endpoint['auth'] ?? true);
        $body = $this->sampleBody($method, (string) ($endpoint['uri'] ?? ltrim((string) ($endpoint['path'] ?? ''), '/')));
        $query = $this->sampleQuery($method, (string) ($endpoint['uri'] ?? ''));
        $multipart = $this->isMultipart((string) ($endpoint['uri'] ?? ''), $method);

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return [
            'curl' => $this->curl($method, $url, $auth, $body, $multipart),
            'php' => $this->php($method, $url, $auth, $body, $multipart),
            'python' => $this->python($method, $url, $auth, $body, $multipart),
            'java' => $this->java($method, $url, $auth, $body, $multipart),
            'javascript' => $this->javascript($method, $url, $auth, $body, $multipart),
            'csharp' => $this->csharp($method, $url, $auth, $body, $multipart),
        ];
    }

    /**
     * Attach `examples` to every endpoint in a catalog payload.
     *
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    public function enrichCatalog(array $catalog, ?string $baseUrl = null): array
    {
        $base = $this->baseUrl($baseUrl);
        $groups = [];

        foreach ($catalog['groups'] ?? [] as $group) {
            $endpoints = [];
            foreach ($group['endpoints'] ?? [] as $endpoint) {
                $endpoint['examples'] = $this->snippetsFor($endpoint, $base);
                $endpoints[] = $endpoint;
            }
            $group['endpoints'] = $endpoints;
            $groups[] = $group;
        }

        $catalog['groups'] = $groups;
        $catalog['example_languages'] = $this->languages();
        $catalog['featured_examples'] = $this->featuredExamples($base);

        return $catalog;
    }

    private function baseUrl(?string $baseUrl): string
    {
        return rtrim($baseUrl ?: (string) config('app.url'), '/');
    }

    private function concretePath(string $path): string
    {
        $index = 0;

        return (string) preg_replace_callback(
            '/\{[^}]+\}/',
            function () use (&$index): string {
                $index++;

                return $index === 1 ? self::SAMPLE_UUID : self::SAMPLE_UUID_NESTED;
            },
            $path
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sampleBody(string $method, string $uri): ?array
    {
        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return null;
        }

        if (str_ends_with($uri, 'auth/login')) {
            return [
                'login' => 'you@example.com',
                'password' => 'YourSecurePassword1!',
                'device_name' => 'mobile-app',
            ];
        }

        if (str_ends_with($uri, 'auth/mfa/verify')) {
            return [
                'challenge_token' => 'YOUR_MFA_CHALLENGE_TOKEN',
                'code' => '123456',
            ];
        }

        if (str_ends_with($uri, 'auth/mfa/resend')) {
            return [
                'challenge_token' => 'YOUR_MFA_CHALLENGE_TOKEN',
            ];
        }

        if (str_ends_with($uri, 'auth/password')) {
            return [
                'current_password' => 'YourCurrentPassword1!',
                'password' => 'YourNewPassword1!',
                'password_confirmation' => 'YourNewPassword1!',
            ];
        }

        if (str_contains($uri, 'password/policy')) {
            return [
                'min_length' => 8,
                'require_mixed_case' => true,
                'require_numbers' => true,
                'require_symbols' => true,
            ];
        }

        if (str_contains($uri, '/logo') || (str_contains($uri, '/documents') && $method === 'POST' && ! str_contains($uri, 'bulk'))) {
            return null;
        }

        if (str_ends_with($uri, 'bulk-category')) {
            return [
                'uuids' => [self::SAMPLE_UUID],
                'category' => 'contracts',
            ];
        }

        if (str_ends_with($uri, 'bulk-delete')) {
            return [
                'uuids' => [self::SAMPLE_UUID],
            ];
        }

        if (str_contains($uri, 'career-history')) {
            return [
                'effective_date' => '2026-01-01',
                'position_title' => 'Analyst',
                'employment_category' => 'regular',
                'basic_salary' => '25000.00',
                'notes' => 'Promotion adjustment',
            ];
        }

        if (str_contains($uri, 'dependents')) {
            return [
                'full_name' => 'Jane Doe',
                'relationship' => 'spouse',
                'birth_date' => '1990-05-01',
            ];
        }

        if (str_contains($uri, 'education')) {
            return [
                'school' => 'Sample University',
                'degree' => 'BS Computer Science',
                'year_graduated' => 2018,
            ];
        }

        if (str_contains($uri, 'employment-history')) {
            return [
                'company' => 'Previous Employer Inc.',
                'position' => 'Staff',
                'start_date' => '2018-01-01',
                'end_date' => '2020-12-31',
            ];
        }

        if (str_contains($uri, '/schedules') && ! str_contains($uri, '/print')) {
            return [
                'employee_uuid' => self::SAMPLE_UUID,
                'shift_uuid' => self::SAMPLE_UUID_NESTED,
                'effective_from' => '2026-08-01',
                'effective_to' => null,
            ];
        }

        if (str_contains($uri, '/shifts')) {
            return [
                'code' => 'DAY',
                'name' => 'Day Shift',
                'time_in' => '08:00',
                'time_out' => '17:00',
                'break_minutes' => 60,
            ];
        }

        if (str_contains($uri, '/holidays')) {
            return [
                'name' => 'Sample Holiday',
                'date' => '2026-12-25',
                'type' => 'regular',
            ];
        }

        if (str_contains($uri, '/departments')) {
            return [
                'code' => 'HR',
                'name' => 'Human Resources',
                'is_active' => true,
            ];
        }

        if (str_contains($uri, '/lookups')) {
            return [
                'type' => 'employment_category',
                'code' => 'regular',
                'label' => 'Regular',
                'is_active' => true,
            ];
        }

        if (str_contains($uri, '/roles')) {
            return [
                'name' => 'Custom Role',
                'permissions' => ['dashboard.view'],
            ];
        }

        if (str_contains($uri, '/users') || str_contains($uri, '/security/users')) {
            return [
                'email' => 'user@example.com',
                'name' => 'Sample User',
                'role_uuids' => [self::SAMPLE_UUID],
                'is_active' => true,
            ];
        }

        if (str_contains($uri, 'system-parameters') || str_contains($uri, 'administration')) {
            return [
                'company_name' => 'PCSPC',
                'timezone' => 'Asia/Manila',
            ];
        }

        if ($uri === 'api/v1/employees') {
            return [
                'employee_number' => 'E-1001',
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'juan@example.com',
            ];
        }

        if (preg_match('#^api/v1/employees/\{[^}]+\}$#', $uri) === 1) {
            return [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'juan@example.com',
            ];
        }

        return [
            'example' => true,
            'note' => 'Replace with validated request fields for this endpoint.',
        ];
    }

    /**
     * @return array<string, scalar>
     */
    private function sampleQuery(string $method, string $uri): array
    {
        if ($method !== 'GET') {
            return [];
        }

        if (str_ends_with($uri, 'schedules/print')) {
            return [
                'scope' => 'employee',
                'effective' => 'current',
                'include_related' => 1,
            ];
        }

        if (str_ends_with($uri, '/employees') || str_ends_with($uri, '/documents') || str_ends_with($uri, '/audit') || str_contains($uri, '/users')) {
            return [
                'per_page' => 10,
                'search' => '',
            ];
        }

        if (str_ends_with($uri, '/search')) {
            return [
                'q' => 'admin',
                'limit' => 10,
            ];
        }

        if (str_contains($uri, '/lookups') && str_contains($uri, 'options')) {
            return [
                'type' => 'employment_category',
            ];
        }

        return [];
    }

    private function isMultipart(string $uri, string $method): bool
    {
        if ($method !== 'POST' && $method !== 'PUT') {
            return false;
        }

        return str_contains($uri, '/logo')
            || (str_contains($uri, '/documents') && ! str_contains($uri, 'bulk'));
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function curl(string $method, string $url, bool $auth, ?array $body, bool $multipart): string
    {
        $lines = ['curl -X '.$method.' "'.$url.'" \\', '  -H "Accept: application/json"'];

        if ($auth) {
            $lines[] = '  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \\';
        }

        if ($multipart) {
            $lines[count($lines) - 1] = rtrim($lines[count($lines) - 1], ' \\');
            if (! str_ends_with($lines[count($lines) - 1], '\\')) {
                $lines[count($lines) - 1] .= ' \\';
            }
            $lines[] = '  -F "file=@/path/to/file.pdf"';

            return implode("\n", $lines);
        }

        if ($body !== null) {
            if (! str_ends_with((string) end($lines), '\\')) {
                $lines[count($lines) - 1] .= ' \\';
            }
            $lines[] = '  -H "Content-Type: application/json" \\';
            $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $lines[] = "  -d '".$json."'";
        } else {
            $lines[count($lines) - 1] = rtrim($lines[count($lines) - 1], ' \\');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function php(string $method, string $url, bool $auth, ?array $body, bool $multipart): string
    {
        $headers = ["'Accept: application/json'"];
        if ($auth) {
            $headers[] = "'Authorization: Bearer ' . \$token";
        }

        $pre = $auth ? "\$token = getenv('PCSPC_TOKEN') ?: 'YOUR_SANCTUM_TOKEN';\n\n" : '';

        if ($multipart) {
            $headers[] = "'Content-Type: multipart/form-data'";
            $code = <<<PHP
{$pre}\$ch = curl_init('{$url}');
curl_setopt_array(\$ch, [
    CURLOPT_CUSTOMREQUEST => '{$method}',
    CURLOPT_HTTPHEADER => [
        {$this->phpArrayLines($headers)}
    ],
    CURLOPT_POSTFIELDS => [
        'file' => new CURLFile('/path/to/file.pdf'),
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

echo curl_exec(\$ch);
curl_close(\$ch);
PHP;

            return $code;
        }

        if ($body !== null) {
            $headers[] = "'Content-Type: application/json'";
            $json = var_export(json_encode($body, JSON_UNESCAPED_SLASHES), true);
            $code = <<<PHP
{$pre}\$payload = {$json};

\$ch = curl_init('{$url}');
curl_setopt_array(\$ch, [
    CURLOPT_CUSTOMREQUEST => '{$method}',
    CURLOPT_HTTPHEADER => [
        {$this->phpArrayLines($headers)}
    ],
    CURLOPT_POSTFIELDS => \$payload,
    CURLOPT_RETURNTRANSFER => true,
]);

echo curl_exec(\$ch);
curl_close(\$ch);
PHP;

            return $code;
        }

        return <<<PHP
{$pre}\$ch = curl_init('{$url}');
curl_setopt_array(\$ch, [
    CURLOPT_CUSTOMREQUEST => '{$method}',
    CURLOPT_HTTPHEADER => [
        {$this->phpArrayLines($headers)}
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

echo curl_exec(\$ch);
curl_close(\$ch);
PHP;
    }

    /**
     * @param  list<string>  $headers
     */
    private function phpArrayLines(array $headers): string
    {
        return implode(",\n        ", $headers);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function python(string $method, string $url, bool $auth, ?array $body, bool $multipart): string
    {
        $pre = "import requests\n";
        if ($auth) {
            $pre .= "import os\n\ntoken = os.getenv(\"PCSPC_TOKEN\", \"YOUR_SANCTUM_TOKEN\")\n";
        }
        $pre .= "\n";

        $headers = ['"Accept": "application/json"'];
        if ($auth) {
            $headers[] = '"Authorization": f"Bearer {token}"';
        }

        $methodLower = strtolower($method);
        $headerBlock = implode(",\n        ", $headers);

        if ($multipart) {
            return <<<PY
{$pre}response = requests.{$methodLower}(
    "{$url}",
    headers={
        {$headerBlock}
    },
    files={"file": open("/path/to/file.pdf", "rb")},
    timeout=30,
)
print(response.json())
PY;
        }

        if ($body !== null) {
            $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $json = $this->indent($json, 4);

            return <<<PY
{$pre}response = requests.{$methodLower}(
    "{$url}",
    headers={
        {$headerBlock}
    },
    json={$json},
    timeout=30,
)
print(response.json())
PY;
        }

        return <<<PY
{$pre}response = requests.{$methodLower}(
    "{$url}",
    headers={
        {$headerBlock}
    },
    timeout=30,
)
print(response.json())
PY;
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function java(string $method, string $url, bool $auth, ?array $body, bool $multipart): string
    {
        $tokenLine = $auth
            ? "String token = System.getenv().getOrDefault(\"PCSPC_TOKEN\", \"YOUR_SANCTUM_TOKEN\");\n"
            : '';
        $authHeader = $auth
            ? "    .header(\"Authorization\", \"Bearer \" + token)\n"
            : '';

        if ($multipart) {
            $authHint = $auth ? ' and Authorization Bearer token' : '';

            return <<<JAVA
// Multipart example — use OkHttp or similar for file uploads.
// {$method} {$url}
{$tokenLine}System.out.println("Upload file with Accept: application/json{$authHint}");
JAVA;
        }

        $bodyPublisher = 'HttpRequest.BodyPublishers.noBody()';
        $contentType = '';
        if ($body !== null) {
            $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $contentType = "    .header(\"Content-Type\", \"application/json\")\n";
            $bodyPublisher = 'HttpRequest.BodyPublishers.ofString("""'."\n".$json."\n".'    """)';
        }

        $methodCall = match ($method) {
            'GET' => '.GET()',
            'DELETE' => '.DELETE()',
            'POST' => '.POST('.$bodyPublisher.')',
            'PUT' => '.PUT('.$bodyPublisher.')',
            'PATCH' => '.method("PATCH", '.$bodyPublisher.')',
            default => '.method("'.$method.'", '.$bodyPublisher.')',
        };

        return <<<JAVA
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;

{$tokenLine}var client = HttpClient.newHttpClient();
var request = HttpRequest.newBuilder()
    .uri(URI.create("{$url}"))
    .header("Accept", "application/json")
{$authHeader}{$contentType}    {$methodCall}
    .build();

HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
System.out.println(response.body());
JAVA;
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function javascript(string $method, string $url, bool $auth, ?array $body, bool $multipart): string
    {
        $pre = $auth ? "const token = process.env.PCSPC_TOKEN || \"YOUR_SANCTUM_TOKEN\";\n\n" : '';
        $authHeader = $auth ? "    Authorization: `Bearer \${token}`,\n" : '';

        if ($multipart) {
            return <<<JS
{$pre}const form = new FormData();
form.append("file", /* File or Blob */);

const response = await fetch("{$url}", {
  method: "{$method}",
  headers: {
    Accept: "application/json",
{$authHeader}  },
  body: form,
});

console.log(await response.json());
JS;
        }

        if ($body !== null) {
            $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return <<<JS
{$pre}const response = await fetch("{$url}", {
  method: "{$method}",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
{$authHeader}  },
  body: JSON.stringify({$json}),
});

console.log(await response.json());
JS;
        }

        return <<<JS
{$pre}const response = await fetch("{$url}", {
  method: "{$method}",
  headers: {
    Accept: "application/json",
{$authHeader}  },
});

console.log(await response.json());
JS;
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function csharp(string $method, string $url, bool $auth, ?array $body, bool $multipart): string
    {
        $pre = "using System.Net.Http.Headers;\n\n";

        if ($auth) {
            $pre .= "var token = Environment.GetEnvironmentVariable(\"PCSPC_TOKEN\") ?? \"YOUR_SANCTUM_TOKEN\";\n";
        }
        $pre .= "var client = new HttpClient();\n";
        $pre .= "client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue(\"application/json\"));\n";
        if ($auth) {
            $pre .= "client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue(\"Bearer\", token);\n";
        }
        $pre .= "\n";

        if ($multipart) {
            return <<<CS
{$pre}using var form = new MultipartFormDataContent();
form.Add(new StreamContent(File.OpenRead(@"/path/to/file.pdf")), "file", "file.pdf");

var response = await client.{$this->csharpSend($method)}("{$url}", form);
var json = await response.Content.ReadAsStringAsync();
Console.WriteLine(json);
CS;
        }

        if ($body !== null) {
            $json = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return <<<CS
{$pre}var content = new StringContent("""
{$json}
""", System.Text.Encoding.UTF8, "application/json");
var response = await client.{$this->csharpSend($method)}("{$url}", content);
response.EnsureSuccessStatusCode();
Console.WriteLine(await response.Content.ReadAsStringAsync());
CS;
        }

        return match ($method) {
            'GET' => <<<CS
{$pre}var json = await client.GetStringAsync("{$url}");
Console.WriteLine(json);
CS,
            'DELETE' => <<<CS
{$pre}var response = await client.DeleteAsync("{$url}");
Console.WriteLine(await response.Content.ReadAsStringAsync());
CS,
            default => <<<CS
{$pre}var request = new HttpRequestMessage(HttpMethod.{$this->csharpMethod($method)}, "{$url}");
var response = await client.SendAsync(request);
Console.WriteLine(await response.Content.ReadAsStringAsync());
CS,
        };
    }

    private function csharpSend(string $method): string
    {
        return match ($method) {
            'PUT' => 'PutAsync',
            'PATCH' => 'PatchAsync',
            default => 'PostAsync',
        };
    }

    private function csharpMethod(string $method): string
    {
        return match ($method) {
            'GET' => 'Get',
            'POST' => 'Post',
            'PUT' => 'Put',
            'DELETE' => 'Delete',
            'PATCH' => 'Patch',
            default => 'Get',
        };
    }

    private function indent(string $text, int $spaces): string
    {
        $pad = str_repeat(' ', $spaces);
        $lines = explode("\n", $text);
        $first = array_shift($lines) ?? '';
        if ($lines === []) {
            return $first;
        }

        return $first."\n".$pad.implode("\n".$pad, $lines);
    }
}
