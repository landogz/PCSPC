@extends('layouts.app')

@section('title', ($module['label'] ?? 'loans') . ' — ' . config('app.name'))
@section('page-title', $module['label'] ?? 'loans')

@section('content')
    <x-modules.page :module="$module" :module-key="$moduleKey" />
@endsection