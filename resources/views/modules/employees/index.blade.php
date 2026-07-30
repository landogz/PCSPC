@extends('layouts.app')

@section('title', ($module['label'] ?? 'employees') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'employees')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection