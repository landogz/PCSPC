@extends('layouts.app')

@section('title', ($module['label'] ?? 'earnings') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'earnings')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection