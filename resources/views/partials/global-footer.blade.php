@php
    $nested = $nested ?? false;
    $repositoryUrl = config('app.repository_url');
    $licenseUrl = config('app.license_url');
    $license = config('app.license');
@endphp

<div
    @class([
        'global-footer fixed inset-x-0 bottom-0 z-40 border-t-2 border-zinc-950 bg-zinc-100 px-4 py-3 font-mono dark:border-zinc-100 dark:bg-zinc-900',
        'lg:static lg:inset-x-auto lg:bottom-auto' => ! $nested,
        'lg:static lg:inset-x-auto lg:bottom-auto lg:border-t-2 lg:bg-transparent lg:px-0 lg:py-0 lg:pt-8 dark:lg:bg-transparent' => $nested,
    ])
    @unless($nested)
        role="contentinfo"
    @endunless
    data-test="global-footer"
>
    <div class="flex flex-col gap-2 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <p class="text-zinc-600 dark:text-zinc-400">
            {{ __('Licensed under :license.', ['license' => $license]) }}
        </p>

        <nav aria-label="{{ __('Open source') }}" class="flex flex-wrap items-center gap-3">
            <a
                href="{{ $repositoryUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="text-zinc-700 transition-colors hover:text-emerald-700 dark:text-zinc-300 dark:hover:text-emerald-400"
            >
                {{ __('Source code') }}
            </a>

            <span aria-hidden="true" class="text-zinc-400">/</span>

            <a
                href="{{ $licenseUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="text-zinc-700 transition-colors hover:text-emerald-700 dark:text-zinc-300 dark:hover:text-emerald-400"
            >
                {{ __('License') }}
            </a>
        </nav>
    </div>
</div>
