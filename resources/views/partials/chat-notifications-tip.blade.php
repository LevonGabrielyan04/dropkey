@unless (auth()->user()->pushSubscriptions()->exists())
    <div class="border-x-2 border-b-2 border-zinc-950 bg-amber-50 dark:border-zinc-100 dark:bg-amber-950/40">
        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-amber-950 dark:text-amber-100">
                {{ $message }}
            </p>

            <a
                href="{{ route('notifications.edit') }}"
                wire:navigate
                class="inline-flex shrink-0 items-center justify-center border-2 border-zinc-950 bg-amber-400 px-3 py-2 text-[10px] font-bold uppercase tracking-[0.18em] text-amber-950 transition-colors hover:bg-amber-300 dark:border-zinc-100"
            >
                {{ __('Notification settings') }}
            </a>
        </div>
    </div>
@endunless
