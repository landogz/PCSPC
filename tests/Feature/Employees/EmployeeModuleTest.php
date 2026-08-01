<?php

namespace Tests\Feature\Employees;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_export_employees_excel(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get('/api/v1/employees/export')
            ->assertOk();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type'),
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.exported')->exists());

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);
        $this->assertSame('PK', substr($content, 0, 2));
    }

    public function test_employee_cannot_export_employees(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->get('/api/v1/employees/export')
            ->assertStatus(403);
    }

    public function test_create_validation_uses_human_error_copy(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/employees', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.first_name.0', 'Please enter a first name.')
            ->assertJsonPath('errors.last_name.0', 'Please enter a last name.')
            ->assertJsonPath('errors.employee_number.0', 'Please enter an employee number.')
            ->assertJsonPath('errors.email.0', 'Please enter a login email.');
    }

    public function test_admin_can_list_employees(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/employees')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['items', 'meta', 'can_manage']]);
    }

    public function test_create_provisions_user_with_employee_role(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP-9001',
                'first_name' => 'New',
                'last_name' => 'Hire',
                'email' => 'newhire@pcspc.local',
                'employment_status' => 'active',
                'position_title' => 'Analyst',
                'tin' => '111-222-333-444',
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.employee.email', 'newhire@pcspc.local')
            ->assertJsonStructure(['data' => ['employee', 'temporary_password']]);

        $temporaryPassword = $response->json('data.temporary_password');
        $this->assertNotEmpty($temporaryPassword);

        $user = User::query()->where('email', 'newhire@pcspc.local')->firstOrFail();
        $this->assertTrue($user->roles()->where('slug', 'employee')->exists());
        $this->assertSame('EMP-9001', $user->employee_number);
        $this->assertTrue(Employee::query()->where('employee_number', 'EMP-9001')->where('user_id', $user->id)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.created')->exists());
    }

    public function test_statutory_fields_are_masked_in_index(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/employees')
            ->assertOk();

        $adminRow = collect($response->json('data.items'))
            ->firstWhere('employee_number', 'EMP-0001');

        $this->assertNotNull($adminRow);
        $this->assertNotSame('123-456-789-000', $adminRow['tin']);
        $this->assertStringEndsWith('-000', $adminRow['tin'] ?? '');
        $this->assertStringContainsString('*', $adminRow['tin'] ?? '');
    }

    public function test_employee_cannot_manage_employees(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->postJson('/api/v1/employees', [
                'employee_number' => 'EMP-9999',
                'first_name' => 'Nope',
                'last_name' => 'Allowed',
                'email' => 'nope@pcspc.local',
                'employment_status' => 'active',
            ])
            ->assertStatus(403);
    }

    public function test_deactivate_disables_linked_user(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $target = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $linkedUser = User::query()->findOrFail($target->user_id);
        $this->assertTrue($linkedUser->is_active);

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$target->uuid}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.employee.employment_status', 'inactive');

        $this->assertFalse($linkedUser->fresh()->is_active);
    }

    public function test_admin_can_upload_and_remove_employee_photo(): void
    {
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $photo = UploadedFile::fake()->image('portrait.jpg', 200, 200);

        $this->actingAs($admin)
            ->post("/api/v1/employees/{$employee->uuid}", [
                '_method' => 'PUT',
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'employment_status' => $employee->employment_status,
                'photo' => $photo,
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $employee->refresh();
        $this->assertNotNull($employee->photo_path);
        Storage::disk('public')->assertExists($employee->photo_path);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.photo_updated')->exists());

        $linkedUser = User::query()->findOrFail($employee->user_id);
        $this->assertNotNull($linkedUser->avatarUrl());
        $this->assertSame($employee->photoUrl(), $linkedUser->avatarUrl());

        $this->actingAs($admin)
            ->post("/api/v1/employees/{$employee->uuid}", [
                '_method' => 'PUT',
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'employment_status' => $employee->employment_status,
                'remove_photo' => '1',
            ])
            ->assertOk()
            ->assertJsonPath('data.employee.photo_url', null);

        $this->assertNull($employee->fresh()->photo_path);
    }
}
