<?php

$mods = [
    'employees', 'departments', 'timekeeping', 'leave', 'overtime', 'workflow',
    'medical', 'training', 'performance', 'compensation', 'documents',
    'loans', 'deductions', 'earnings', 'travel', 'reports',
    'administration', 'security', 'audit', 'notifications', 'help',
];

$base = dirname(__DIR__);

foreach ($mods as $m) {
    $viewDir = "{$base}/resources/views/modules/{$m}";
    $jsDir = "{$base}/resources/js/modules/{$m}";
    if (! is_dir($viewDir)) {
        mkdir($viewDir, 0775, true);
    }
    if (! is_dir($jsDir)) {
        mkdir($jsDir, 0775, true);
    }

    $blade = <<<BLADE
@extends('layouts.app')

@section('title', (\$module['label'] ?? '{$m}') . ' — ' . config('app.name'))
@section('page-title', \$module['label'] ?? '{$m}')

@section('content')
    <x-modules.page :module="\$module" :module-key="\$moduleKey" />
@endsection
BLADE;
    file_put_contents("{$viewDir}/index.blade.php", $blade);

    $fn = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $m)));
    $js = <<<JS
/**
 * {$m} module (SPA)
 * See config/navigation.php and docs/MODULES.md
 * Future: Axios + DataTables against /api/v1/{$m}
 */
export function init{$fn}Module() {
    // Scaffold only — feature APIs land with the mapped phase.
}
JS;
    file_put_contents("{$jsDir}/index.js", $js);
    echo "created {$m}\n";
}
