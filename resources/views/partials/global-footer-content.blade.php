@php
    $repositoryUrl = config('app.repository_url');
    $licenseUrl = config('app.license_url');
    $license = config('app.license');
@endphp

<div class="flex flex-row flex-wrap items-center justify-between gap-x-4 gap-y-2 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-500">
    <p class="min-w-0 text-zinc-600 dark:text-zinc-400">
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
