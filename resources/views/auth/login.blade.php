@extends('layouts.guest')

@section('title', 'Sign in — '.config('app.name'))
@section('page-title', 'Sign In')

@section('content')
<main class="min-h-dvh flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="bg-surface border border-border rounded-3xl shadow-xl shadow-black/5 p-6 sm:p-8">
            <a href="{{ url('/') }}" class="flex justify-center mb-6 sm:mb-8" aria-label="{{ config('app.name') }} home">
                <x-brand.logo variant="compact" class="pcspc-login-logo !max-w-none" />
            </a>

            <h2 class="text-xl font-bold text-heading text-center">Welcome back</h2>
            <p class="text-sm text-muted text-center mt-1">
                Sign in to continue to your dashboard
            </p>

            <form id="login-form" class="space-y-4 mt-6" novalidate>
                <div id="login-step" class="space-y-4">
                    <div>
                        <label for="login" class="block text-xs font-medium text-text-secondary mb-1.5">
                            Email or employee number
                        </label>
                        <div class="relative">
                            <i class="ph ph-envelope-simple absolute left-3 top-1/2 -translate-y-1/2 text-muted"></i>
                            <input
                                id="login"
                                name="login"
                                type="text"
                                autocomplete="username"
                                required
                                placeholder="admin@pcspc.local or EMP-0001"
                                class="w-full h-11 pl-9 pr-3 rounded-xl bg-subtle border border-border text-sm text-text placeholder:text-faint focus:outline-none focus:border-primary transition-colors"
                            >
                        </div>
                        <p data-error="login" class="mt-1.5 text-sm text-danger hidden"></p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-medium text-text-secondary">
                                Password
                            </label>
                        </div>
                        <x-ui.password-input
                            name="password"
                            id="password"
                            autocomplete="current-password"
                            :required="true"
                            placeholder="Enter your password"
                        />
                        <p data-error="password" class="mt-1.5 text-sm text-danger hidden"></p>
                    </div>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="remember" name="remember" class="accent-primary">
                        <span class="text-xs text-text-secondary">Keep me signed in</span>
                    </label>

                    <button
                        type="submit"
                        id="login-submit"
                        class="w-full inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
                    >
                        Sign in<i class="ph ph-arrow-right text-base"></i>
                    </button>

                    @if (app()->environment(['local', 'testing']))
                        <div class="border-t border-border-subtle pt-4">
                            <p class="mb-3 text-[11px] font-bold tracking-wide text-faint uppercase">
                                Dev auto-login
                            </p>
                            <div class="grid gap-2">
                                <button
                                    type="button"
                                    class="w-full inline-flex items-center justify-between gap-2 h-11 px-3 rounded-xl border border-border text-sm hover:border-border-strong hover:bg-subtle transition-colors text-left"
                                    data-auto-login
                                    data-login="admin@pcspc.local"
                                    data-password="Password1!"
                                >
                                    <span class="font-semibold text-heading">Admin</span>
                                    <span class="truncate text-xs text-muted">admin@pcspc.local</span>
                                </button>
                                <button
                                    type="button"
                                    class="w-full inline-flex items-center justify-between gap-2 h-11 px-3 rounded-xl border border-border text-sm hover:border-border-strong hover:bg-subtle transition-colors text-left"
                                    data-auto-login
                                    data-login="employee@pcspc.local"
                                    data-password="Password1!"
                                >
                                    <span class="font-semibold text-heading">Employee</span>
                                    <span class="truncate text-xs text-muted">employee@pcspc.local</span>
                                </button>
                                <button
                                    type="button"
                                    class="w-full inline-flex items-center justify-between gap-2 h-11 px-3 rounded-xl border border-border text-sm hover:border-border-strong hover:bg-subtle transition-colors text-left"
                                    data-auto-login
                                    data-login="{{ env('MFA_DEMO_EMAIL', 'mfa@pcspc.local') }}"
                                    data-password="Password1!"
                                    data-auto-mfa="1"
                                >
                                    <span class="font-semibold text-heading">MFA Admin</span>
                                    <span class="truncate text-xs text-muted">{{ env('MFA_DEMO_EMAIL', 'mfa@pcspc.local') }}</span>
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-warning">
                                Local only — hidden outside local/testing.
                            </p>
                        </div>
                    @endif
                </div>

                <div id="mfa-step" class="hidden space-y-4">
                    <p class="text-sm text-muted">
                        Enter the 6-digit code we emailed you
                        <span class="font-medium text-heading" data-mfa-email></span>.
                    </p>
                    <div>
                        <label for="otp" class="block text-xs font-medium text-text-secondary mb-1.5">
                            Authentication code
                        </label>
                        <input
                            id="otp"
                            name="otp"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            autocomplete="one-time-code"
                            class="w-full h-11 px-3 rounded-xl bg-subtle border border-border text-sm text-text text-center tracking-[0.3em] placeholder:text-faint focus:outline-none focus:border-primary transition-colors"
                            placeholder="000000"
                        >
                        <p data-error="otp" class="mt-1.5 text-sm text-danger hidden"></p>
                        <p data-error="mfa_token" class="mt-1.5 text-sm text-danger hidden"></p>
                    </div>

                    <button
                        type="button"
                        id="mfa-submit"
                        class="w-full inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-strong transition-colors"
                    >
                        Verify &amp; continue
                    </button>
                    <button
                        type="button"
                        id="mfa-resend"
                        class="w-full inline-flex items-center justify-center gap-1.5 h-11 rounded-xl border border-border text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors"
                    >
                        Resend code
                    </button>
                    <button
                        type="button"
                        id="mfa-back"
                        class="w-full inline-flex items-center justify-center gap-1.5 h-11 rounded-xl border border-border text-sm font-medium text-text-secondary hover:border-border-strong hover:bg-subtle transition-colors"
                    >
                        Back to sign in
                    </button>
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-[11px] text-muted mt-5">
            <span class="inline-flex items-center gap-1">
                <i class="ph ph-shield-check text-success"></i>Secure sign-in
            </span>
            <span class="inline-flex items-center gap-1">
                <i class="ph ph-lock-key text-success"></i>Activity logged
            </span>
            <a href="{{ route('api-docs') }}" class="inline-flex items-center gap-1 hover:text-heading transition-colors">
                <i class="ph ph-code"></i>API docs
            </a>
        </div>

        <p class="text-center text-[11px] text-faint mt-4">
            © {{ date('Y') }} Philippine Coastal Storage &amp; Pipeline Corporation
        </p>
    </div>
</main>
@endsection
