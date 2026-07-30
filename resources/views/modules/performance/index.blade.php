@extends('layouts.app')

@section('title', ($module['label'] ?? 'performance') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'performance')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection