@extends('layouts.app')

@section('title', ($module['label'] ?? 'notifications') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'notifications')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection