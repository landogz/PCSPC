<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Inject the global search service.
     */
    public function __construct(
        private readonly SearchService $search,
    ) {}

    /**
     * Return mega-menu search results for modules, people, and shortcuts.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $limit = min(max((int) $request->integer('limit', 8), 1), 20);

        return ApiResponse::success('Search results retrieved.', $this->search->search(
            $request->user(),
            $query,
            $limit,
        ));
    }
}
