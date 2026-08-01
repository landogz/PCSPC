<?php

namespace Tests\Feature\Employees;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeEducationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    public function test_admin_can_manage_employee_educations(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $create = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/educations", [
                'institution' => 'PCSPC University',
                'level' => 'bachelor',
                'degree_or_course' => 'BS Computer Science',
                'year_started' => 2015,
                'year_ended' => 2019,
                'is_highest' => true,
                'honors' => 'Cum Laude',
            ])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.education.institution', 'PCSPC University')
            ->assertJsonPath('data.education.is_highest', true);

        $educationId = $create->json('data.education.id');
        $this->assertNotEmpty($educationId);
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.education.created')->exists());

        $this->actingAs($admin)
            ->getJson("/api/v1/employees/{$employee->uuid}/educations")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $educationId)
            ->assertJsonStructure(['data' => ['items', 'levels']]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employee->uuid}/educations/{$educationId}", [
                'institution' => 'PCSPC University',
                'level' => 'master',
                'degree_or_course' => 'MS Information Systems',
                'year_started' => 2020,
                'year_ended' => 2022,
                'is_highest' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.education.level', 'master')
            ->assertJsonPath('data.education.degree_or_course', 'MS Information Systems');

        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.education.updated')->exists());

        $this->actingAs($admin)
            ->deleteJson("/api/v1/employees/{$employee->uuid}/educations/{$educationId}")
            ->assertOk();

        $this->assertFalse(EmployeeEducation::query()->where('uuid', $educationId)->exists());
        $this->assertTrue(AuthActivityLog::query()->where('event', 'employee.education.deleted')->exists());
    }

    public function test_marking_highest_clears_previous_highest(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $first = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/educations", [
                'institution' => 'First School',
                'level' => 'bachelor',
                'is_highest' => true,
            ])
            ->assertCreated()
            ->json('data.education.id');

        $second = $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/educations", [
                'institution' => 'Second School',
                'level' => 'master',
                'is_highest' => true,
            ])
            ->assertCreated()
            ->json('data.education.id');

        $this->assertFalse(EmployeeEducation::query()->where('uuid', $first)->value('is_highest'));
        $this->assertTrue(EmployeeEducation::query()->where('uuid', $second)->value('is_highest'));
    }

    public function test_education_validation_requires_institution_and_level(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson("/api/v1/employees/{$employee->uuid}/educations", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.institution.0', 'Please enter the school or institution.')
            ->assertJsonPath('errors.level.0', 'Please choose an education level.');
    }

    public function test_employee_cannot_manage_educations(): void
    {
        $employeeUser = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();

        $this->actingAs($employeeUser)
            ->getJson("/api/v1/employees/{$employee->uuid}/educations")
            ->assertStatus(403);

        $this->actingAs($employeeUser)
            ->postJson("/api/v1/employees/{$employee->uuid}/educations", [
                'institution' => 'Nope',
                'level' => 'bachelor',
            ])
            ->assertStatus(403);
    }

    public function test_education_belongs_to_employee_scope(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employeeA = Employee::query()->where('employee_number', 'EMP-1001')->firstOrFail();
        $employeeB = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $education = EmployeeEducation::query()->create([
            'employee_id' => $employeeA->id,
            'institution' => 'Scoped School',
            'level' => 'bachelor',
        ]);

        $this->actingAs($admin)
            ->putJson("/api/v1/employees/{$employeeB->uuid}/educations/{$education->uuid}", [
                'institution' => 'Scoped School',
                'level' => 'bachelor',
            ])
            ->assertNotFound();
    }
}
