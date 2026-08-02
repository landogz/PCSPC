<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocsPageController extends Controller
{
    private const ALLOWED = [
        'modules' => 'MODULES.md',
        'project-plan' => 'PROJECT_PLAN.md',
        'flowcharts' => 'FLOWCHARTS.md',
    ];

    /**
     * Render an in-app documentation page from a whitelisted markdown file.
     */
    public function show(string $doc): View
    {
        $file = self::ALLOWED[$doc] ?? null;
        if ($file === null) {
            throw new NotFoundHttpException();
        }

        $path = base_path('docs/'.$file);
        if (! File::exists($path)) {
            throw new NotFoundHttpException();
        }

        return view('docs.show', [
            'doc' => $doc,
            'title' => match ($doc) {
                'modules' => 'Menu ↔ Module Map',
                'project-plan' => 'Project Plan',
                'flowcharts' => 'Flowcharts',
                default => 'Documentation',
            },
            'filename' => $file,
            'content' => File::get($path),
        ]);
    }
}
