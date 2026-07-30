@extends('layouts.app')

@section('title', ($module['label'] ?? 'workflow') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'workflow')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection