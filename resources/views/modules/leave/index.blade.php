@extends('layouts.app')

@section('title', ($module['label'] ?? 'leave') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'leave')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection