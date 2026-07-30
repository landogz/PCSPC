@extends('layouts.app')

@section('title', ($module['label'] ?? 'deductions') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'deductions')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection