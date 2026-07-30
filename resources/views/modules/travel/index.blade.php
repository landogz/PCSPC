@extends('layouts.app')

@section('title', ($module['label'] ?? 'travel') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'travel')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection