<x-layouts::app :title="__('Messages')">
    <div class="flex h-full w-full flex-1 flex-col font-mono p-6">
        <flux:heading size="xl" class="!font-mono !text-2xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
            {{ __('Messages') }}
        </flux:heading>

        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Pairwise encrypted channels. UI coming in the next step.') }}
        </p>

        @if ($conversations->isEmpty())
            <p class="mt-6 text-sm text-zinc-500">{{ __('No conversations yet.') }}</p>
        @else
            <ul class="mt-6 divide-y-2 divide-zinc-950 dark:divide-zinc-100">
                @foreach ($conversations as $conversation)
                    @php($partner = $conversation->partnerFor(auth()->user()))
                    <li>
                        <a
                            href="{{ route('chat.show', $partner) }}"
                            wire:navigate
                            class="block py-4 text-sm font-bold uppercase tracking-[0.16em] text-zinc-950 hover:text-emerald-700 dark:text-zinc-50 dark:hover:text-emerald-400"
                        >
                            {{ $partner->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts::app>
