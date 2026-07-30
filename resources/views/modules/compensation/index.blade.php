@extends('layouts.app')

@section('title', ($module['label'] ?? 'compensation') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'compensation')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection