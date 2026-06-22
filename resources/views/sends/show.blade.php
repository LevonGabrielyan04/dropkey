<x-layouts::app :title="__('Send Details')">
    <div class="mx-auto w-full max-w-2xl">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
            <x-send-details :send="$send" />
        </div>
    </div>
</x-layouts::app>
