@extends('layouts.app')

@section('title', ($module['label'] ?? 'timekeeping') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'timekeeping')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection