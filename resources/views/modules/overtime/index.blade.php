@extends('layouts.app')

@section('title', ($module['label'] ?? 'overtime') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'overtime')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection