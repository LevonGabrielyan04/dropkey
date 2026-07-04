<x-layouts::app :title="__('Messages')">
    <div class="flex h-full w-full flex-1 flex-col font-mono">
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Encrypted inbox') }}
            </div>

            <div class="p-6">
                <flux:heading size="xl" class="!font-mono !text-2xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                    {{ __('Messages') }}
                </flux:heading>

                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Pairwise encrypted channels. Plaintext never touches our servers.') }}
                </p>
            </div>
        </header>

        <section
            x-data="e2eeChatInbox"
            data-chat-base-url="{{ url('/chat') }}"
            class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100"
        >
            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Start a conversation') }}
                </p>
            </div>

            <form @submit.prevent="startChat" class="bg-white p-6 dark:bg-zinc-950">
                <label for="chat-username" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Recipient user name') }}
                </label>

                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input
                        id="chat-username"
                        type="text"
                        x-model="username"
                        placeholder="{{ __('Enter a registered user name') }}"
                        class="block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50"
                    />

                    <button
                        type="submit"
                        class="inline-flex shrink-0 cursor-pointer items-center justify-center !rounded-none border-2 border-zinc-950 bg-emerald-500 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 dark:border-zinc-100"
                    >
                        {{ __('Open channel') }}
                    </button>
                </div>

                <span x-show="error" x-text="error" x-cloak class="mt-2 block text-sm text-red-600"></span>
            </form>
        </section>

        <section class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100">
            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Recent conversations') }}
                </p>
            </div>

            @if ($conversations->isEmpty())
                <p class="bg-white p-6 text-sm text-zinc-500 dark:bg-zinc-950">
                    {{ __('No conversations yet.') }}
                </p>
            @else
                <ul class="divide-y-2 divide-zinc-950 bg-white dark:divide-zinc-100 dark:bg-zinc-950">
                    @foreach ($conversations as $conversation)
                        @php($partner = $conversation->partnerFor(auth()->user()))
                        <li>
                            <a
                                href="{{ route('chat.show', $partner) }}"
                                wire:navigate
                                class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-900"
                            >
                                <span class="text-sm font-bold uppercase tracking-[0.16em] text-zinc-950 dark:text-zinc-50">
                                    {{ $partner->name }}
                                </span>

                                @if ($conversation->messages->isNotEmpty())
                                    <span
                                        x-data="localDatetime"
                                        data-datetime="{{ $conversation->messages->first()->created_at->toIso8601String() }}"
                                        x-text="formatted"
                                        class="text-[10px] uppercase tracking-[0.14em] text-zinc-500"
                                    ></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts::app>
