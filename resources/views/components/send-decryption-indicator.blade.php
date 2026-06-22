<div
    x-show="isDecrypting"
    x-cloak
    class="flex items-center justify-center gap-2 mb-3 text-sm font-medium text-zinc-600 dark:text-zinc-400"
    role="status"
    aria-live="polite"
>
    <span class="loading loading-spinner loading-sm text-primary"></span>
    <span>{{ __('Decryption in progress') }}</span>
</div>
