<?php

namespace Tests\Feature\Documents;

use App\Models\AuthActivityLog;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Database\Seeders\AuthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
        Storage::fake('local');
    }

    public function test_admin_can_upload_list_download_and_delete_document(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();
        $file = UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf');

        $create = $this->actingAs($admin)
            ->post('/api/v1/documents', [
                'employee_id' => $employee->uuid,
                'title' => 'Employment contract',
                'category' => 'contract',
                'issued_at' => '2026-01-01',
                'expires_at' => now()->addDays(10)->toDateString(),
                'notes' => 'Primary contract',
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.document.title', 'Employment contract')
            ->assertJsonPath('data.document.category', 'contract');

        $documentId = $create->json('data.document.id');
        $this->assertNotEmpty($documentId);

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'document.created')->exists()
        );

        $this->actingAs($admin)
            ->getJson('/api/v1/documents?expiry=expiring')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonFragment(['title' => 'Employment contract']);

        $path = EmployeeDocument::query()->where('uuid', $documentId)->value('file_path');
        Storage::disk('local')->assertExists($path);

        $this->actingAs($admin)
            ->get("/api/v1/documents/{$documentId}/download")
            ->assertOk();

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'document.downloaded')->exists()
        );

        $this->actingAs($admin)
            ->deleteJson("/api/v1/documents/{$documentId}")
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseMissing('employee_documents', ['uuid' => $documentId]);
        Storage::disk('local')->assertMissing($path);
        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'document.deleted')->exists()
        );
    }

    public function test_employee_cannot_access_documents_api_or_module_page(): void
    {
        $employee = User::query()->where('email', 'employee@pcspc.local')->firstOrFail();

        $this->actingAs($employee)
            ->getJson('/api/v1/documents')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get('/modules/documents')
            ->assertForbidden();
    }

    public function test_upload_requires_file_and_valid_category(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/v1/documents', [
                'employee_id' => $employee->uuid,
                'title' => 'Missing file',
                'category' => 'not-a-category',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file', 'category']);
    }

    public function test_admin_can_open_documents_module_page(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/modules/documents')
            ->assertOk()
            ->assertSee('Document repository', false)
            ->assertSee('DOC-001', false)
            ->assertSee('Expiring soon', false)
            ->assertSee('Repository storage', false)
            ->assertSee('data-employee-search', false);
    }

    public function test_list_includes_stats_and_preview_metadata(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();
        $file = UploadedFile::fake()->image('id.png', 100, 100);

        $this->actingAs($admin)
            ->post('/api/v1/documents', [
                'employee_id' => $employee->uuid,
                'title' => 'Government ID scan',
                'category' => 'government_id',
                'expires_at' => now()->addDays(5)->toDateString(),
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->actingAs($admin)
            ->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.stats.expiring', 1)
            ->assertJsonStructure([
                'data' => [
                    'items' => [[
                        'file_kind',
                        'expiry_status',
                        'is_previewable',
                        'preview_url',
                        'access' => ['level', 'label'],
                    ]],
                    'stats' => ['total', 'expiring', 'expired', 'valid', 'by_category', 'storage'],
                ],
            ]);
    }

    public function test_reupload_archives_previous_version_and_preview_works(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $create = $this->actingAs($admin)
            ->post('/api/v1/documents', [
                'employee_id' => $employee->uuid,
                'title' => 'Contract',
                'category' => 'contract',
                'file' => UploadedFile::fake()->create('v1.pdf', 80, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $documentId = $create->json('data.document.id');
        $oldPath = EmployeeDocument::query()->where('uuid', $documentId)->value('file_path');

        $this->actingAs($admin)
            ->post("/api/v1/documents/{$documentId}", [
                'title' => 'Contract',
                'category' => 'contract',
                'employee_id' => $employee->uuid,
                'file' => UploadedFile::fake()->create('v2.pdf', 90, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.document.version_count', 1);

        $this->assertDatabaseHas('employee_document_versions', [
            'original_name' => 'v1.pdf',
        ]);
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($admin)
            ->get("/api/v1/documents/{$documentId}/preview")
            ->assertOk();

        $this->assertTrue(
            AuthActivityLog::query()->where('event', 'document.previewed')->exists()
        );
    }

    public function test_bulk_category_and_delete(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-0001')->firstOrFail();

        $ids = [];
        foreach (['a.pdf', 'b.pdf'] as $name) {
            $response = $this->actingAs($admin)
                ->post('/api/v1/documents', [
                    'employee_id' => $employee->uuid,
                    'title' => $name,
                    'category' => 'other',
                    'file' => UploadedFile::fake()->create($name, 40, 'application/pdf'),
                ], ['Accept' => 'application/json'])
                ->assertCreated();
            $ids[] = $response->json('data.document.id');
        }

        $this->actingAs($admin)
            ->postJson('/api/v1/documents/bulk-category', [
                'ids' => $ids,
                'category' => 'policy',
            ])
            ->assertOk()
            ->assertJsonPath('data.updated', 2);

        $this->assertSame(2, EmployeeDocument::query()->where('category', 'policy')->count());

        $this->actingAs($admin)
            ->postJson('/api/v1/documents/bulk-delete', ['ids' => $ids])
            ->assertOk()
            ->assertJsonPath('data.deleted', 2);

        $this->assertSame(0, EmployeeDocument::query()->whereIn('uuid', $ids)->count());
    }

    public function test_admin_can_search_employees_for_document_picker(): void
    {
        $admin = User::query()->where('email', 'admin@pcspc.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/employees/search?search=EMP-0001')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['items' => [['id', 'employee_number', 'full_name', 'label']]]]);
    }
}
