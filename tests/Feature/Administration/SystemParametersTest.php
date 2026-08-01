<?php

namespace Tests\Feature\Administration;

use App\Models\AuthActivityLog;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemParametersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_view_and_update_system_parameters(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/administration/system-parameters')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.parameters.company_short_name', 'PCSPC')
            ->assertJsonPath('data.parameters.timezone', 'Asia/Manila')
            ->assertJsonPath('data.parameters.rest_day_holiday_paid_hours', 8)
            ->assertJsonPath('data.parameters.has_custom_logo', false)
            ->assertJsonStructure([
                'data' => [
                    'parameters' => ['logo_url', 'has_custom_logo', 'logo_path'],
                    'meta' => ['timezones', 'date_formats', 'week_starts'],
                ],
            ]);

        $this->actingAs($admin)
            ->putJson('/api/v1/administration/system-parameters', [
                'company_name' => 'PCSPC Demo Corp',
                'company_short_name' => 'PCSPC',
                'timezone' => 'Asia/Manila',
                'date_format' => 'd/m/Y',
                'currency_code' => 'PHP',
                'support_email' => 'hr@pcspc.local',
                'leave_year_start_month' => 7,
                'rest_day_holiday_paid_hours' => 8,
                'default_grace_minutes' => 10,
                'week_start' => 'monday',
            ])
            ->assertOk()
            ->assertJsonPath('data.parameters.company_name', 'PCSPC Demo Corp')
            ->assertJsonPath('data.parameters.leave_year_start_month', 7)
            ->assertJsonPath('data.parameters.default_grace_minutes', 10)
            ->assertJsonPath('data.parameters.date_format', 'd/m/Y');

        $this->assertTrue(AuthActivityLog::query()->where('event', 'system_parameters.updated')->exists());

        $this->actingAs($admin)
            ->getJson('/api/v1/administration/system-parameters')
            ->assertOk()
            ->assertJsonPath('data.parameters.company_name', 'PCSPC Demo Corp');
    }

    public function test_admin_can_upload_and_reset_company_logo(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $logo = UploadedFile::fake()->image('company-logo.png', 400, 120);

        $this->actingAs($admin)
            ->post('/api/v1/administration/system-parameters/logo', [
                'logo' => $logo,
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.parameters.has_custom_logo', true)
            ->assertJsonPath('data.parameters.logo_path', 'brand/company-logo.png')
            ->assertJsonPath('data.parameters.logo_url', '/storage/brand/company-logo.png');

        Storage::disk('public')->assertExists('brand/company-logo.png');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'system_parameters.logo_updated')->exists());

        $this->actingAs($admin)
            ->deleteJson('/api/v1/administration/system-parameters/logo')
            ->assertOk()
            ->assertJsonPath('data.parameters.has_custom_logo', false)
            ->assertJsonPath('data.parameters.logo_path', null);

        Storage::disk('public')->assertMissing('brand/company-logo.png');
        $this->assertTrue(AuthActivityLog::query()->where('event', 'system_parameters.logo_removed')->exists());
    }

    public function test_logo_upload_rejects_invalid_file(): void
    {
        Storage::fake('public');
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->post('/api/v1/administration/system-parameters/logo', [
                'logo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_system_parameters_validation_rejects_invalid_payload(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/api/v1/administration/system-parameters', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.company_name.0', 'Please enter the company name.')
            ->assertJsonPath('errors.support_email.0', 'Please enter a support email.');
    }

    public function test_employee_cannot_manage_system_parameters(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/administration/system-parameters')
            ->assertStatus(403);

        $this->actingAs($employee)
            ->putJson('/api/v1/administration/system-parameters', [
                'company_name' => 'Nope',
                'company_short_name' => 'X',
                'timezone' => 'Asia/Manila',
                'date_format' => 'Y-m-d',
                'currency_code' => 'PHP',
                'support_email' => 'x@pcspc.local',
                'leave_year_start_month' => 1,
                'rest_day_holiday_paid_hours' => 8,
                'default_grace_minutes' => 0,
                'week_start' => 'monday',
            ])
            ->assertStatus(403);
    }
}
