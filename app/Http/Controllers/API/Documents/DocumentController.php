<?php

namespace App\Http\Controllers\API\Documents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\BulkCategoryDocumentsRequest;
use App\Http\Requests\Documents\BulkDeleteDocumentsRequest;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Requests\Documents\UpdateDocumentRequest;
use App\Http\Resources\Documents\DocumentResource;
use App\Services\Documents\DocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 10), 1), 100);

        $paginator = $this->documents->list([
            'search' => (string) $request->query('search', ''),
            'category' => (string) $request->query('category', ''),
            'employee_id' => (string) $request->query('employee_id', ''),
            'expiry' => (string) $request->query('expiry', ''),
        ], $perPage);

        return ApiResponse::success('Documents retrieved.', [
            'items' => DocumentResource::collection($paginator->getCollection())->resolve(),
            'categories' => $this->documents->categories(),
            'stats' => $this->documents->stats(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        return ApiResponse::success('Document stats retrieved.', [
            'stats' => $this->documents->stats(),
            'categories' => $this->documents->categories(),
        ]);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documents->create(
            $request->validated(),
            $request->file('file'),
            $request->user()?->id,
        );

        return ApiResponse::success('Document uploaded.', [
            'document' => (new DocumentResource($document))->resolve(),
        ], 201);
    }

    public function show(string $document): JsonResponse
    {
        $model = $this->documents->find($document);

        return ApiResponse::success('Document retrieved.', [
            'document' => (new DocumentResource($model))->resolve(),
        ]);
    }

    public function update(UpdateDocumentRequest $request, string $document): JsonResponse
    {
        $model = $this->documents->update(
            $document,
            $request->validated(),
            $request->file('file'),
            $request->user()?->id,
        );

        return ApiResponse::success('Document updated.', [
            'document' => (new DocumentResource($model))->resolve(),
        ]);
    }

    public function destroy(string $document): JsonResponse
    {
        $this->documents->delete($document);

        return ApiResponse::success('Document deleted.');
    }

    public function bulkDestroy(BulkDeleteDocumentsRequest $request): JsonResponse
    {
        $result = $this->documents->bulkDelete($request->validated('ids'));

        return ApiResponse::success(
            $result['deleted'] === 1
                ? 'Document deleted.'
                : "{$result['deleted']} documents deleted.",
            $result,
        );
    }

    public function bulkCategory(BulkCategoryDocumentsRequest $request): JsonResponse
    {
        $result = $this->documents->bulkUpdateCategory(
            $request->validated('ids'),
            $request->validated('category'),
        );

        return ApiResponse::success(
            $result['updated'] === 1
                ? 'Category updated.'
                : "{$result['updated']} documents updated.",
            $result,
        );
    }

    public function download(string $document): StreamedResponse
    {
        return $this->documents->download($document);
    }

    public function preview(string $document): StreamedResponse
    {
        return $this->documents->preview($document);
    }

    public function downloadVersion(string $document, string $version): StreamedResponse
    {
        return $this->documents->downloadVersion($document, $version);
    }
}
