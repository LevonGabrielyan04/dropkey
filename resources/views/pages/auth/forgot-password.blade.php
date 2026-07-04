<x-layouts::auth :title="__('Forgot password')">
    <div class="flex w-full flex-col font-mono">
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Credential recovery') }}
            </div>

            <div class="p-6">
                <flux:heading size="xl" class="!font-mono !text-2xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                    {{ __('Forgot password') }}
                </flux:heading>

                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ __('Enter your email to receive a password reset link') }}
                </p>
            </div>
        </header>

        @if (session('status'))
            <div
                class="border-x-2 border-b-2 border-zinc-950 bg-emerald-500/10 px-6 py-4 dark:border-zinc-100"
                role="status"
            >
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400">
                    {{ __('Status') }}
                </p>
                <p class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                    {{ session('status') }}
                </p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100">
            @csrf

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Contact') }}
                </p>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="email" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Email address') }}
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="email@example.com"
                    required
                    autofocus
                    autocomplete="email"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('email') border-red-600 @enderror"
                />

                @error('email')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            @if (config('turnstile.enabled'))
                <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                        {{ __('Verification') }}
                    </p>
                </div>

                <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                    <x-turnstile />
                </div>
            @endif

            <div class="border-b-2 border-emerald-500 bg-emerald-500/10 px-6 py-4 dark:border-emerald-400">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400">
                    {{ __('Recovery policy') }}
                </p>
                <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                    {{ __('Reset links expire after a short window. Request a new one if the email does not arrive.') }}
                </p>
            </div>

            <div class="bg-zinc-50 p-6 dark:bg-zinc-900">
                <button
                    type="submit"
                    data-test="email-password-reset-link-button"
                    class="inline-flex w-full cursor-pointer items-center justify-center !rounded-none border-2 border-zinc-950 bg-emerald-500 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 dark:border-zinc-100"
                >
                    {{ __('Email password reset link') }}
                </button>
            </div>
        </form>

        <div class="border-x-2 border-b-2 border-zinc-950 bg-zinc-100 px-6 py-4 text-center dark:border-zinc-100 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Or, return to') }}</span>
                <flux:link
                    :href="route('login')"
                    wire:navigate
                    class="!font-mono !text-xs !font-bold !uppercase !tracking-[0.16em] !text-zinc-700 hover:!text-emerald-700 dark:!text-zinc-300 dark:hover:!text-emerald-400"
                >
                    {{ __('log in') }}
                </flux:link>
            </p>
        </div>
    </div>
</x-layouts::auth>
