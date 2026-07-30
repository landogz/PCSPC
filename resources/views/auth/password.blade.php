@extends('layouts.guest')

@section('title', 'Change password — '.config('app.name'))
@section('page-title', 'Change Password')

@section('content')
<main class="min-h-dvh flex items-center justify-center px-4 py-10" data-module="change-password">
    <div class="w-full max-w-md">
        <div class="bg-surface border border-border rounded-3xl shadow-xl shadow-black/5 p-6 sm:p-8">
            <a href="{{ url('/') }}" class="flex justify-center mb-6 sm:mb-8" aria-label="{{ config('app.name') }} home">
                <img
                    src="{{ asset('images/brand/pcspc-logo.png') }}"
                    alt="{{ config('app.name') }}"
                    width="650"
                    height="200"
                    decoding="async"
                    class="pcspc-login-logo"
                >
            </a>

            <h2 class="text-xl font-bold text-heading text-center">Change your password</h2>
            <p class="text-sm text-muted text-center mt-1" data-password-reason>
                Update your password to continue.
            </p>
            <p class="text-xs text-muted text-center mt-3" data-password-hint></p>

            <form id="change-password-form" class="space-y-4 mt-6" novalidate>
                <div>
                    <label for="current_password" class="block text-xs font-medium text-text-secondary mb-1.5">
                        Current password
                    </label>
                    <x-ui.password-input
                        name="current_password"
                        id="current_password"
                        autocomplete="current-password"
                        :required="true"
                    />
                    <p data-error="current_password" class="mt-1.5 text-sm text-danger hidden"></p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-text-secondary mb-1.5">
                        New password
                    </label>
                    <x-ui.password-input
                        name="password"
                        id="password"
                        autocomplete="new-password"
                        :required="true"
                    />
                    <p data-error="password" class="mt-1.5 text-sm text-danger hidden"></p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-text-secondary mb-1.5">
                        Confirm new password
                    </label>
                    <x-ui.password-input
                        name="password_confirmation"
                        id="password_confirmation"
                        autocomplete="new-password"
                        :required="true"
                    />
                    <p data-error="password_confirmation" class="mt-1.5 text-sm text-danger hidden"></p>
                </div>

                <button
                    type="submit"
                    id="change-password-submit"
                    class="w-full inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
                >
                    Save new password
                </button>
            </form>
        </div>
    </div>
</main>
@endsection
