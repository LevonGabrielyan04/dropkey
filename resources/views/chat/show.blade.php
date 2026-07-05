<x-layouts::app :title="__('Chat with :name', ['name' => $recipient->name])">
    <div
        class="flex h-full w-full flex-1 flex-col font-mono"
        x-data="e2eeChatSession"
        data-local-user-id="{{ auth()->id() }}"
        data-recipient-id="{{ $recipient->id }}"
        data-csrf-token="{{ csrf_token() }}"
        data-public-key-url="{{ route('api.users.public-key.show', $recipient) }}"
        data-register-url="{{ route('api.identity.public-key.store') }}"
        data-messages-url="{{ route('messages.index', $recipient) }}"
        data-send-url="{{ route('messages.store') }}"
        data-poll-interval-ms="{{ config('chat.poll_interval_ms') }}"
        data-decryption-failed-message="{{ __('Unable to decrypt this message.') }}"
    >
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Pairwise encrypted channel') }}
            </div>

            <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <flux:heading size="xl" class="!font-mono !text-2xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                        {{ $recipient->name }}
                    </flux:heading>

                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Messages are encrypted in your browser before transmission. The server only relays ciphertext.') }}
                    </p>

                    <p
                        x-show="ready && partnerFingerprint"
                        x-text="'{{ __('Partner fingerprint') }}: ' + partnerFingerprint"
                        x-cloak
                        class="mt-2 break-all text-[10px] uppercase tracking-[0.14em] text-zinc-500"
                    ></p>
                </div>

                <flux:button
                    :href="route('chat.index')"
                    icon="arrow-left"
                    wire:navigate
                    class="!rounded-none !border-2 !border-zinc-950 !bg-zinc-50 !px-4 !py-3 !text-xs !font-bold !uppercase !tracking-[0.18em] !text-zinc-950 hover:!bg-zinc-200 dark:!border-zinc-100 dark:!bg-zinc-950 dark:!text-zinc-50 dark:hover:!bg-zinc-800"
                >
                    {{ __('Inbox') }}
                </flux:button>
            </div>
        </header>

        <div class="border-x-2 border-b-2 border-zinc-950 bg-white p-4 dark:border-zinc-100 dark:bg-zinc-950">
            <p x-show="loading" x-cloak class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Establishing encrypted session...') }}
            </p>

            <p x-show="error" x-text="error" x-cloak class="text-sm text-red-600"></p>

            <p x-show="ready" x-cloak class="text-sm text-emerald-700 dark:text-emerald-400">
                {{ __('Encrypted session ready.') }}
            </p>
        </div>

        <div
            x-ref="messageList"
            x-show="ready"
            x-cloak
            class="flex max-h-[28rem] min-h-[12rem] flex-1 flex-col gap-3 overflow-y-auto border-x-2 border-b-2 border-zinc-950 bg-zinc-50 p-4 dark:border-zinc-100 dark:bg-zinc-900"
        >
            <template x-if="messages.length === 0">
                <p class="text-sm text-zinc-500">{{ __('No messages yet. Send the first encrypted message below.') }}</p>
            </template>

            <template x-for="message in messages" :key="message.id">
                <article
                    class="max-w-[85%] border-2 px-3 py-2 text-sm"
                    :class="message.decryptionError
                        ? (message.isMine
                            ? 'ms-auto border-red-600 bg-red-50 text-red-700 dark:border-red-500 dark:bg-red-950/40 dark:text-red-400'
                            : 'me-auto border-red-600 bg-red-50 text-red-700 dark:border-red-500 dark:bg-red-950/40 dark:text-red-400')
                        : (message.isMine
                            ? 'ms-auto border-zinc-950 bg-emerald-500/20 text-zinc-950 dark:border-zinc-100 dark:text-zinc-50'
                            : 'me-auto border-zinc-950 bg-white text-zinc-950 dark:border-zinc-100 dark:bg-zinc-950 dark:text-zinc-50')"
                >
                    <p
                        x-show="!message.decryptionError"
                        class="whitespace-pre-wrap break-words"
                        x-text="message.plaintext"
                    ></p>
                    <p
                        x-show="message.decryptionError"
                        x-cloak
                        class="whitespace-pre-wrap break-words"
                        x-text="message.decryptionError"
                    ></p>
                    <p
                        class="mt-1 text-[10px] uppercase tracking-[0.14em] text-zinc-500"
                        x-text="message.isMine ? '{{ __('You') }}' : '{{ $recipient->name }}'"
                    ></p>
                </article>
            </template>
        </div>

        <form
            @submit.prevent="sendMessage"
            class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100"
        >
            <div class="border-b-2 border-zinc-950 bg-zinc-200 px-4 py-3 dark:border-zinc-100 dark:bg-zinc-800">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                    {{ __('Compose') }}
                </p>
            </div>

            <div class="bg-white p-6 dark:bg-zinc-950">
                <label for="message" class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                    {{ __('Message') }}
                </label>

                <textarea
                    id="message"
                    x-model="messageText"
                    rows="4"
                    placeholder="{{ __('Type your message...') }}"
                    class="mt-2 block w-full !rounded-none border-2 border-zinc-950 bg-white px-3 py-2.5 font-mono text-sm text-zinc-950 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:border-zinc-100 dark:bg-zinc-900 dark:text-zinc-50"
                    :disabled="!ready || sending"
                ></textarea>

                <span x-show="sendError" x-text="sendError" x-cloak class="mt-2 block text-sm text-red-600"></span>
            </div>

            <div class="bg-zinc-50 p-6 dark:bg-zinc-900">
                <button
                    type="submit"
                    class="inline-flex w-full cursor-pointer items-center justify-center !rounded-none border-2 border-zinc-950 bg-emerald-500 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-100"
                    :disabled="!canSendMessage"
                >
                    <span x-show="!sending">{{ __('Send encrypted message') }}</span>
                    <span x-show="sending" x-cloak>{{ __('Encrypting...') }}</span>
                </button>
            </div>
        </form>
    </div>
</x-layouts::app>
