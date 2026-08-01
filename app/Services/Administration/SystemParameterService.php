<?php

namespace App\Services\Administration;

use App\Repositories\Administration\SystemSettingRepository;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SystemParameterService
{
    public const SETTINGS_KEY = 'system_parameters';

    public const LOGO_DIRECTORY = 'brand';

    private const DEFAULT_LOGO_ASSET = 'images/brand/pcspc-logo.png';

    public function __construct(
        private readonly SystemSettingRepository $settings,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{
     *     company_name: string,
     *     company_short_name: string,
     *     timezone: string,
     *     date_format: string,
     *     currency_code: string,
     *     support_email: string,
     *     leave_year_start_month: int,
     *     rest_day_holiday_paid_hours: int,
     *     default_grace_minutes: int,
     *     week_start: string,
     *     logo_path: string|null,
     *     logo_url: string,
     *     has_custom_logo: bool
     * }
     */
    public function current(): array
    {
        $defaults = config('system_parameters.defaults', []);
        $stored = $this->settings->get(self::SETTINGS_KEY);

        $params = array_merge(
            is_array($defaults) ? $defaults : [],
            is_array($stored) ? $stored : [],
        );

        $logoPath = filled($params['logo_path'] ?? null) ? (string) $params['logo_path'] : null;
        if ($logoPath !== null && ! Storage::disk('public')->exists($logoPath)) {
            $logoPath = null;
        }

        return [
            'company_name' => trim((string) ($params['company_name'] ?? '')),
            'company_short_name' => trim((string) ($params['company_short_name'] ?? '')),
            'timezone' => (string) ($params['timezone'] ?? 'Asia/Manila'),
            'date_format' => (string) ($params['date_format'] ?? 'Y-m-d'),
            'currency_code' => strtoupper(trim((string) ($params['currency_code'] ?? 'PHP'))),
            'support_email' => trim((string) ($params['support_email'] ?? '')),
            'leave_year_start_month' => max(1, min(12, (int) ($params['leave_year_start_month'] ?? 1))),
            'rest_day_holiday_paid_hours' => max(0, min(24, (int) ($params['rest_day_holiday_paid_hours'] ?? 8))),
            'default_grace_minutes' => max(0, min(120, (int) ($params['default_grace_minutes'] ?? 0))),
            'week_start' => (string) ($params['week_start'] ?? 'monday'),
            'logo_path' => $logoPath,
            'logo_url' => $this->resolveLogoUrl($logoPath, absolute: false),
            'has_custom_logo' => $logoPath !== null,
        ];
    }

    /**
     * Public logo URL for UI (relative) or email (absolute).
     */
    public function logoUrl(bool $absolute = false): string
    {
        return $this->resolveLogoUrl($this->current()['logo_path'], $absolute);
    }

    /**
     * Absolute filesystem path for the active company logo (custom upload or default asset).
     * Used to embed images in outbound email.
     */
    public function logoFilesystemPath(): string
    {
        $logoPath = $this->current()['logo_path'];

        if (filled($logoPath) && Storage::disk('public')->exists($logoPath)) {
            return Storage::disk('public')->path($logoPath);
        }

        return public_path(self::DEFAULT_LOGO_ASSET);
    }

    /**
     * @return array{timezones: list<string>, date_formats: list<string>, week_starts: list<string>}
     */
    public function meta(): array
    {
        return [
            'timezones' => array_values(config('system_parameters.timezones', ['Asia/Manila', 'UTC'])),
            'date_formats' => array_values(config('system_parameters.date_formats', ['Y-m-d'])),
            'week_starts' => array_values(config('system_parameters.week_starts', ['monday', 'sunday'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(array $payload): array
    {
        $current = $this->current();

        $next = [
            'company_name' => trim((string) ($payload['company_name'] ?? $current['company_name'])),
            'company_short_name' => trim((string) ($payload['company_short_name'] ?? $current['company_short_name'])),
            'timezone' => (string) ($payload['timezone'] ?? $current['timezone']),
            'date_format' => (string) ($payload['date_format'] ?? $current['date_format']),
            'currency_code' => strtoupper(trim((string) ($payload['currency_code'] ?? $current['currency_code']))),
            'support_email' => trim((string) ($payload['support_email'] ?? $current['support_email'])),
            'leave_year_start_month' => (int) ($payload['leave_year_start_month'] ?? $current['leave_year_start_month']),
            'rest_day_holiday_paid_hours' => (int) ($payload['rest_day_holiday_paid_hours'] ?? $current['rest_day_holiday_paid_hours']),
            'default_grace_minutes' => (int) ($payload['default_grace_minutes'] ?? $current['default_grace_minutes']),
            'week_start' => (string) ($payload['week_start'] ?? $current['week_start']),
            'logo_path' => $current['logo_path'],
        ];

        $this->settings->put(self::SETTINGS_KEY, $next);

        $params = $this->current();

        $this->audit->log('system_parameters.updated', [
            'company_short_name' => $params['company_short_name'],
            'timezone' => $params['timezone'],
            'leave_year_start_month' => $params['leave_year_start_month'],
            'rest_day_holiday_paid_hours' => $params['rest_day_holiday_paid_hours'],
            'default_grace_minutes' => $params['default_grace_minutes'],
            'week_start' => $params['week_start'],
            'has_custom_logo' => $params['has_custom_logo'],
        ]);

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    public function storeLogo(UploadedFile $logo): array
    {
        $current = $this->current();
        $this->deleteStoredLogo($current['logo_path']);

        $extension = strtolower($logo->getClientOriginalExtension() ?: $logo->extension() ?: 'png');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'png';
        }

        $path = $logo->storeAs(
            self::LOGO_DIRECTORY,
            'company-logo.'.$extension,
            'public'
        );

        $next = $this->persistableSettings($current);
        $next['logo_path'] = $path;
        $this->settings->put(self::SETTINGS_KEY, $next);

        $params = $this->current();

        $this->audit->log('system_parameters.logo_updated', [
            'logo_path' => $params['logo_path'],
            'has_custom_logo' => true,
        ]);

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    public function clearLogo(): array
    {
        $current = $this->current();
        $this->deleteStoredLogo($current['logo_path']);

        $next = $this->persistableSettings($current);
        $next['logo_path'] = null;
        $this->settings->put(self::SETTINGS_KEY, $next);

        $params = $this->current();

        $this->audit->log('system_parameters.logo_removed', [
            'has_custom_logo' => false,
        ]);

        return $params;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function persistableSettings(array $params): array
    {
        return [
            'company_name' => $params['company_name'],
            'company_short_name' => $params['company_short_name'],
            'timezone' => $params['timezone'],
            'date_format' => $params['date_format'],
            'currency_code' => $params['currency_code'],
            'support_email' => $params['support_email'],
            'leave_year_start_month' => $params['leave_year_start_month'],
            'rest_day_holiday_paid_hours' => $params['rest_day_holiday_paid_hours'],
            'default_grace_minutes' => $params['default_grace_minutes'],
            'week_start' => $params['week_start'],
            'logo_path' => $params['logo_path'],
        ];
    }

    private function resolveLogoUrl(?string $logoPath, bool $absolute): string
    {
        if (filled($logoPath) && Storage::disk('public')->exists($logoPath)) {
            // Root-relative so login/sidebar work on any host/port (APP_URL may differ from the browser).
            $relative = '/storage/'.ltrim(str_replace('\\', '/', $logoPath), '/');

            return $absolute ? url($relative) : $relative;
        }

        $default = '/'.ltrim(self::DEFAULT_LOGO_ASSET, '/');

        return $absolute ? url($default) : $default;
    }

    private function deleteStoredLogo(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
