@extends('layouts.app')

@section('title', ($module['label'] ?? 'departments') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'departments')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection