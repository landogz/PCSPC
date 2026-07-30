@extends('layouts.app')

@section('title', ($module['label'] ?? 'training') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'training')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection