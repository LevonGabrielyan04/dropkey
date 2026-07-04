<div
    x-show="isEncrypting"
    x-cloak
    class="mb-4 flex items-center justify-center gap-2 border-2 border-zinc-950 bg-zinc-100 px-4 py-3 font-mono text-xs font-bold uppercase tracking-[0.16em] text-zinc-700 dark:border-zinc-100 dark:bg-zinc-800 dark:text-zinc-300"
    role="status"
    aria-live="polite"
>
    <span class="loading loading-spinner loading-sm text-emerald-600"></span>
    <span>{{ __('Encryption in progress') }}</span>
</div>
