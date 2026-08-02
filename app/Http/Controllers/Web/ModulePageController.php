<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class ModulePageController extends Controller
{
    /**
     * Render a permission-gated SPA module page when the view exists.
     */
    public function show(string $module): View|Response
    {
        $item = Navigation::find($module);

        if ($item === null || ($item['key'] ?? null) === 'dashboard') {
            abort(404);
        }

        $user = request()->user();

        if (! Navigation::userCanAccess($user, $item)) {
            abort(403, 'You do not have permission to access this module.');
        }

        $view = "modules.{$module}.index";

        if (! view()->exists($view)) {
            abort(404, "Module view [{$view}] is missing.");
        }

        return view($view, [
            'module' => $item,
            'moduleKey' => $module,
        ]);
    }
}
