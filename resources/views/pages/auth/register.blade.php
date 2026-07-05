<x-layouts::auth :title="__('Register')">
    <div class="flex w-full flex-col font-mono">
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Operator enrollment') }}
            </div>

            <div class="p-6">
                <flux:heading size="xl" class="!font-mono !text-2xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                    {{ __('Create an account') }}
                </flux:heading>

                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ __('Enter your nickname and password below. Email is optional.') }}
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

        <form method="POST" action="{{ route('register.store') }}" class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100">
            @csrf

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Identity') }}
                </p>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="name" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Nickname') }}
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="{{ __('Nickname') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('name') border-red-600 @enderror"
                />

                @error('name')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Contact') }}
                </p>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="email" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Email address (optional)') }}
                </label>

                <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                    {{ __('Optional. Add an email if you want password recovery and email-based features.') }}
                </p>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="email@example.com"
                    autocomplete="email"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('email') border-red-600 @enderror"
                />

                @error('email')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Credentials') }}
                </p>
            </div>

            <div class="border-b-2 border-zinc-950 bg-white p-6 dark:border-zinc-100 dark:bg-zinc-950">
                <label for="password" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Password') }}
                </label>

                <div class="relative mt-2" x-data="passwordVisibility">
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Password') }}"
                        passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                        class="block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 pr-12 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50 @error('password') border-red-600 @enderror"
                    />

                    <button
                        type="button"
                        @click="toggle"
                        class="absolute right-3 top-1/2 -translate-y-1/2 border-2 border-transparent p-1 text-zinc-600 transition-colors hover:border-zinc-950 hover:text-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-100 dark:hover:text-zinc-100"
                        :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                    >
                        <flux:icon.eye x-show="!showPassword" x-cloak variant="outline" class="size-4" />
                        <flux:icon.eye-slash x-show="showPassword" x-cloak variant="outline" class="size-4" />
                    </button>
                </div>

                @error('password')
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
                    {{ __('Before you enroll') }}
                </p>
                <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                    {{ __('Without an email, password recovery is unavailable. Store your credentials securely.') }}
                </p>
            </div>

            <div class="bg-zinc-50 p-6 dark:bg-zinc-900">
                <button
                    type="submit"
                    data-test="register-user-button"
                    class="inline-flex w-full cursor-pointer items-center justify-center !rounded-none border-2 border-zinc-950 bg-emerald-500 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 dark:border-zinc-100"
                >
                    {{ __('Create account') }}
                </button>
            </div>
        </form>

        <div class="border-x-2 border-b-2 border-zinc-950 bg-zinc-100 px-6 py-4 text-center dark:border-zinc-100 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Already have an account?') }}</span>
                <flux:link
                    :href="route('login')"
                    wire:navigate
                    class="!font-mono !text-xs !font-bold !uppercase !tracking-[0.16em] !text-zinc-700 hover:!text-emerald-700 dark:!text-zinc-300 dark:hover:!text-emerald-400"
                >
                    {{ __('Log in') }}
                </flux:link>
            </p>
        </div>
    </div>
</x-layouts::auth>
