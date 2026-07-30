@extends('layouts.app')

@section('title', ($module['label'] ?? 'reports') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'reports')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection