<x-layouts::app :title="__('Send Details')">
    <div class="flex h-full w-full flex-1 flex-col font-mono">
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Secure channel dossier') }}
            </div>

            <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <flux:heading size="xl" class="!font-mono !text-3xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                        {{ $send->name }}
                    </flux:heading>

                    <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ __('This payload was encrypted in your browser. Only listed viewers can open it before the expiry window closes.') }}
                    </p>
                </div>

                <div class="inline-flex flex-wrap items-center gap-3">
                    <flux:button
                        :href="route('dashboard')"
                        icon="arrow-left"
                        wire:navigate
                        class="!rounded-none !border-2 !border-zinc-950 !bg-zinc-50 !px-4 !py-3 !text-xs !font-bold !uppercase !tracking-[0.18em] !text-zinc-950 hover:!bg-zinc-200 dark:!border-zinc-100 dark:!bg-zinc-950 dark:!text-zinc-50 dark:hover:!bg-zinc-800"
                    >
                        {{ __('Registry') }}
                    </flux:button>

                    <span
                        x-data="copyText"
                        data-copy-text="{{ route('sends.show', $send) }}"
                        class="inline-flex"
                    >
                        <button
                            type="button"
                            @click="copy"
                            class="inline-flex cursor-pointer items-center gap-2 !rounded-none border-2 border-zinc-950 bg-emerald-500 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-emerald-950 transition-colors hover:bg-emerald-400 dark:border-zinc-100"
                            title="{{ __('Copy link') }}"
                        >
                            <flux:icon.document-duplicate x-show="!copied" variant="outline" class="size-4" />
                            <flux:icon.check x-show="copied" variant="solid" class="size-4" />
                            {{ __('Copy link') }}
                        </button>
                    </span>

                    @can('forceDelete', $send)
                        <flux:modal.trigger name="delete-send-{{ $send->id }}">
                            <button
                                type="button"
                                class="inline-flex cursor-pointer items-center gap-2 !rounded-none border-2 border-zinc-950 px-4 py-3 text-xs font-bold uppercase tracking-[0.18em] text-zinc-700 transition-colors hover:border-red-600 hover:text-red-600 dark:border-zinc-100 dark:text-zinc-300 dark:hover:border-red-400 dark:hover:text-red-400"
                                title="{{ __('Revoke send') }}"
                            >
                                <flux:icon.trash variant="outline" class="size-4" />
                                {{ __('Revoke') }}
                            </button>
                        </flux:modal.trigger>
                    @endcan
                </div>
            </div>
        </header>

        <x-send-details :send="$send" />

        @can('forceDelete', $send)
            <flux:modal name="delete-send-{{ $send->id }}" :closable="false" class="max-w-md !rounded-none !border-2 !border-zinc-950 dark:!border-zinc-100">
                <form method="POST" action="{{ route('sends.destroy', $send) }}" class="space-y-6 font-mono">
                    @csrf
                    @method('DELETE')

                    <div class="space-y-3 border-b-2 border-zinc-950 pb-4 dark:border-zinc-100">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600 dark:text-red-400">
                            {{ __('Destructive action') }}
                        </p>

                        <flux:heading size="lg" class="!font-mono !font-black !uppercase !tracking-tight">
                            {{ __('Revoke this send permanently?') }}
                        </flux:heading>

                        <flux:text class="!font-mono !text-sm">
                            {{ __('The ciphertext is deleted immediately. Authorized viewers lose access.') }}
                        </flux:text>
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button
                                variant="filled"
                                class="!rounded-none !border-2 !border-zinc-950 !font-bold !uppercase !tracking-[0.16em] dark:!border-zinc-100"
                            >
                                {{ __('Cancel') }}
                            </flux:button>
                        </flux:modal.close>

                        <flux:button
                            variant="danger"
                            type="submit"
                            class="!rounded-none !border-2 !border-red-700 !font-bold !uppercase !tracking-[0.16em]"
                        >
                            {{ __('Revoke') }}
                        </flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcan
    </div>
</x-layouts::app>
