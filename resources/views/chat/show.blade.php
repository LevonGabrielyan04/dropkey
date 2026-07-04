<x-layouts::app :title="__('Chat with :name', ['name' => $recipient->name])">
    <div class="flex h-full w-full flex-1 flex-col font-mono p-6">
        <flux:heading size="xl" class="!font-mono !text-2xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
            {{ $recipient->name }}
        </flux:heading>

        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Pairwise encrypted channel. Client UI coming in the next step.') }}
        </p>
    </div>
</x-layouts::app>
