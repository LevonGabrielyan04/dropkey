<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form
            method="POST"
            action="{{ route('password.email') }}"
            class="flex flex-col gap-6"
            x-data="{
                turnstileRequired: @json(config('turnstile.enabled')),
                turnstileVerified: false,
            }"
            x-on:turnstile-verified="turnstileVerified = true"
            x-on:turnstile-expired="turnstileVerified = false"
            x-on:turnstile-error="turnstileVerified = false"
        >
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <x-turnstile />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
                data-test="email-password-reset-link-button"
                x-bind:disabled="turnstileRequired && ! turnstileVerified"
            >
                {{ __('Email password reset link') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
