<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ApiDocs\ApiCatalogService;
use App\Services\ApiDocs\ApiExampleService;
use App\Support\ApiResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ApiDocsController extends Controller
{
    /**
     * Inject API catalog and example generation services.
     */
    public function __construct(
        private readonly ApiCatalogService $catalog,
        private readonly ApiExampleService $examples,
    ) {}

    /**
     * Render the public API reference page with enriched code examples.
     */
    public function index(): View
    {
        $catalog = $this->examples->enrichCatalog($this->catalog->catalog());

        return view('api-docs.index', [
            'catalog' => $catalog,
            'exampleLanguages' => $catalog['example_languages'],
            'examples' => $catalog['featured_examples'],
            'useAppLayout' => auth()->check(),
        ]);
    }

    /**
     * Return the API catalog as JSON for programmatic consumption.
     */
    public function json(): JsonResponse
    {
        return ApiResponse::success(
            'API catalog retrieved.',
            $this->examples->enrichCatalog($this->catalog->catalog())
        );
    }
}
