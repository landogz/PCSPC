@extends('layouts.app')

@section('title', ($module['label'] ?? 'medical') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'medical')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection