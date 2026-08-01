@extends('layouts.app')

@section('title', ($module['label'] ?? 'Medical Records') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'Medical Records')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey">
        <x-modules.phase-stub
            req-label="EMP-006 / MED-001"
            :employees-href="route('modules.show', ['module' => 'employees'])"
            :upcoming="[
                'Annual physical exam (APE) and medical notes on Employee 201',
                'Vaccination and clinic visit history',
                'Medical reimbursement claims (P6)',
                'Encrypted sensitive fields and restricted RBAC',
            ]"
        />
    </x-modules.page>
@endsection
