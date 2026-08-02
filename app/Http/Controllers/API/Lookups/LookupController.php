<?php

namespace App\Http\Controllers\API\Lookups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lookups\StoreLookupValueRequest;
use App\Http\Requests\Lookups\UpdateLookupValueRequest;
use App\Http\Resources\Lookups\LookupValueResource;
use App\Services\Lookups\LookupService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Inject the lookup service.
     */
    public function __construct(
        private readonly LookupService $lookups,
    ) {}

    /**
     * List all configured lookup type definitions.
     */
    public function types(): JsonResponse
    {
        return ApiResponse::success('Lookup types retrieved.', [
            'types' => $this->lookups->types(),
        ]);
    }

    /**
     * List lookup values with search, type, status filters, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $paginator = $this->lookups->list([
            'search' => (string) $request->query('search', ''),
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ], $perPage);

        return ApiResponse::success('Lookup values retrieved.', [
            'items' => LookupValueResource::collection($paginator->getCollection())->resolve(),
            'types' => $this->lookups->types(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return active lookup options for one or more requested types.
     */
    public function options(Request $request): JsonResponse
    {
        $raw = (string) $request->query('types', '');
        $requested = array_values(array_filter(array_map('trim', explode(',', $raw))));
        $known = array_keys(config('lookups.types', []));
        $types = $requested === [] ? $known : array_values(array_intersect($requested, $known));

        $options = [];
        foreach ($types as $type) {
            $options[$type] = $this->lookups->activeOptions($type);
        }

        return ApiResponse::success('Lookup options retrieved.', [
            'options' => $options,
        ]);
    }

    /**
     * Create a new lookup value record.
     */
    public function store(StoreLookupValueRequest $request): JsonResponse
    {
        $lookup = $this->lookups->create($request->validated());

        return ApiResponse::success('Lookup value created.', [
            'lookup' => (new LookupValueResource($lookup))->resolve(),
        ], 201);
    }

    /**
     * Return a single lookup value by UUID.
     */
    public function show(string $lookup): JsonResponse
    {
        $model = $this->lookups->find($lookup);

        return ApiResponse::success('Lookup value retrieved.', [
            'lookup' => (new LookupValueResource($model))->resolve(),
        ]);
    }

    /**
     * Update an existing lookup value.
     */
    public function update(UpdateLookupValueRequest $request, string $lookup): JsonResponse
    {
        $model = $this->lookups->update($lookup, $request->validated());

        return ApiResponse::success('Lookup value updated.', [
            'lookup' => (new LookupValueResource($model))->resolve(),
        ]);
    }

    /**
     * Permanently delete a lookup value.
     */
    public function destroy(string $lookup): JsonResponse
    {
        $this->lookups->delete($lookup);

        return ApiResponse::success('Lookup value deleted.');
    }
}
