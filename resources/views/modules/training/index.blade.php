@extends('layouts.app')

@section('title', ($module['label'] ?? 'Training') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Training')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey">
        <x-modules.phase-stub
            req-label="EMP-005 / TRN-001"
            :employees-href="route('modules.show', ['module' => 'employees'])"
            :upcoming="[
                'Training & seminar records on Employee 201',
                'Certification tracking and expiry reminders',
                'Training confirmation workflow',
                'Module DataTables listing with Axios SPA CRUD',
            ]"
        />
    </x-modules.page>
@endsection
