<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col font-mono">
        <header class="border-2 border-zinc-950 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-950">
            <div class="border-b-2 border-emerald-500 bg-emerald-500 px-4 py-1 text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-950">
                {{ __('Encrypted outbound channel registry') }}
            </div>

            <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <flux:heading size="xl" class="!font-mono !text-3xl !font-black !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50">
                        {{ __('Your Sends') }}
                    </flux:heading>

                    <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ __('Secrets leave your browser encrypted. This list is the only record of what you have shared and who may open it.') }}
                    </p>
                </div>

                <flux:button
                    :href="route('sends.create')"
                    icon="plus"
                    wire:navigate
                    class="!rounded-none !border-2 !border-zinc-950 !bg-emerald-500 !px-5 !py-3 !text-xs !font-bold !uppercase !tracking-[0.18em] !text-emerald-950 hover:!bg-emerald-400 dark:!border-zinc-100"
                >
                    {{ __('New Send') }}
                </flux:button>
            </div>
        </header>

        @if (session('success'))
            <div
                class="border-x-2 border-b-2 border-zinc-950 bg-emerald-500/10 px-6 py-4 dark:border-zinc-100"
                role="status"
            >
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-400">
                    {{ __('Status') }}
                </p>
                <p class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                    {{ session('success') }}
                </p>
            </div>
        @endif

        @if ($sends->isEmpty())
            <section class="border-x-2 border-b-2 border-zinc-950 bg-zinc-100 px-6 py-16 text-center dark:border-zinc-100 dark:bg-zinc-900">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-zinc-500">
                    {{ __('Registry empty') }}
                </p>

                <flux:text class="mt-4 !font-mono !text-base !text-zinc-800 dark:!text-zinc-200">
                    {{ __('No sends yet.') }}
                </flux:text>

                <flux:button
                    :href="route('sends.create')"
                    variant="primary"
                    wire:navigate
                    class="mt-8 !rounded-none !border-2 !border-zinc-950 !bg-emerald-500 !px-6 !py-3 !text-xs !font-bold !uppercase !tracking-[0.18em] !text-emerald-950 hover:!bg-emerald-400 dark:!border-zinc-100"
                >
                    {{ __('Create your first send') }}
                </flux:button>
            </section>
        @else
            <section class="border-x-2 border-b-2 border-zinc-950 dark:border-zinc-100">
                <div class="grid grid-cols-2 border-b-2 border-zinc-950 dark:border-zinc-100 sm:grid-cols-3">
                    <div class="border-r-2 border-zinc-950 px-4 py-4 dark:border-zinc-100">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                            {{ __('Active sends') }}
                        </p>
                        <p class="mt-2 text-3xl font-black tabular-nums text-zinc-950 dark:text-zinc-50">
                            {{ $sends->count() }}
                        </p>
                    </div>

                    <div class="border-r-2 border-zinc-950 px-4 py-4 dark:border-zinc-100 sm:col-span-2">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                            {{ __('Policy') }}
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                            {{ __('Each row is one time-bound payload. Revoke by deleting the send before expiry.') }}
                        </p>
                    </div>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="border-b-2 border-zinc-950 bg-zinc-200 text-left dark:border-zinc-100 dark:bg-zinc-800">
                                <th scope="col" class="w-16 border-r-2 border-zinc-950 px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:border-zinc-100 dark:text-zinc-400">
                                    #
                                </th>
                                <th scope="col" class="border-r-2 border-zinc-950 px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:border-zinc-100 dark:text-zinc-400">
                                    {{ __('Name') }}
                                </th>
                                <th scope="col" class="border-r-2 border-zinc-950 px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:border-zinc-100 dark:text-zinc-400">
                                    {{ __('Expires') }}
                                </th>
                                <th scope="col" class="border-r-2 border-zinc-950 px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:border-zinc-100 dark:text-zinc-400">
                                    {{ __('Viewers') }}
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-600 dark:text-zinc-400">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sends as $index => $send)
                                <tr @class([
                                    'border-b-2 border-zinc-950 dark:border-zinc-100',
                                    'bg-zinc-50 dark:bg-zinc-900' => $loop->even,
                                    'bg-white dark:bg-zinc-950' => $loop->odd,
                                ])>
                                    <td class="border-r-2 border-zinc-950 px-4 py-4 text-sm font-bold tabular-nums text-emerald-700 dark:border-zinc-100 dark:text-emerald-400">
                                        {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="border-r-2 border-zinc-950 px-4 py-4 text-sm font-bold text-zinc-950 dark:border-zinc-100 dark:text-zinc-50">
                                        <flux:link
                                            :href="route('sends.show', $send)"
                                            wire:navigate
                                            class="!font-mono !font-bold !uppercase !tracking-tight !text-zinc-950 hover:!text-emerald-700 dark:!text-zinc-50 dark:hover:!text-emerald-400"
                                        >
                                            {{ $send->name }}
                                        </flux:link>
                                    </td>
                                    <td class="border-r-2 border-zinc-950 px-4 py-4 text-sm tabular-nums text-zinc-700 dark:border-zinc-100 dark:text-zinc-300">
                                        <span
                                            x-data="localDatetime"
                                            data-utc-datetime="{{ \Illuminate\Support\Carbon::parse($send->valid_to)->utc()->toIso8601String() }}"
                                            x-text="formatted"
                                        ></span>
                                    </td>
                                    <td class="border-r-2 border-zinc-950 px-4 py-4 text-sm text-zinc-700 dark:border-zinc-100 dark:text-zinc-300">
                                        {{ $send->authorizedUsers->pluck('name')->join(', ') ?: __('None') }}
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        <div class="inline-flex items-center justify-end gap-3">
                                            <flux:link
                                                :href="route('sends.show', $send)"
                                                wire:navigate
                                                class="!font-mono !text-xs !font-bold !uppercase !tracking-[0.16em] !text-zinc-700 hover:!text-emerald-700 dark:!text-zinc-300 dark:hover:!text-emerald-400"
                                            >
                                                {{ __('View') }}
                                            </flux:link>

                                            <span
                                                x-data="copyText"
                                                data-copy-text="{{ route('sends.show', $send) }}"
                                                class="inline-flex"
                                            >
                                                <button
                                                    type="button"
                                                    @click="copy"
                                                    class="inline-flex cursor-pointer border-2 border-transparent p-1 text-zinc-600 transition-colors hover:border-zinc-950 hover:text-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-100 dark:hover:text-zinc-100"
                                                    title="{{ __('Copy link') }}"
                                                >
                                                    <flux:icon.document-duplicate x-show="!copied" variant="outline" class="size-4" />
                                                    <flux:icon.check x-show="copied" variant="solid" class="size-4 text-emerald-500" />
                                                </button>
                                            </span>

                                            @can('forceDelete', $send)
                                                <flux:modal.trigger name="delete-send-{{ $send->id }}">
                                                    <button
                                                        type="button"
                                                        class="inline-flex cursor-pointer border-2 border-transparent p-1 text-zinc-600 transition-colors hover:border-red-600 hover:text-red-600 dark:text-zinc-400 dark:hover:border-red-400 dark:hover:text-red-400"
                                                        title="{{ __('Delete') }}"
                                                    >
                                                        <flux:icon.trash variant="outline" class="size-4" />
                                                    </button>
                                                </flux:modal.trigger>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y-2 divide-zinc-950 dark:divide-zinc-100 md:hidden">
                    @foreach ($sends as $index => $send)
                        <article @class([
                            'p-4',
                            'bg-zinc-50 dark:bg-zinc-900' => $loop->even,
                            'bg-white dark:bg-zinc-950' => $loop->odd,
                        ])>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">
                                        #{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                    </p>

                                    <flux:link
                                        :href="route('sends.show', $send)"
                                        wire:navigate
                                        class="mt-2 block !font-mono !text-base !font-bold !uppercase !tracking-tight !text-zinc-950 dark:!text-zinc-50"
                                    >
                                        {{ $send->name }}
                                    </flux:link>
                                </div>

                                <div class="inline-flex items-center gap-2">
                                    <span
                                        x-data="copyText"
                                        data-copy-text="{{ route('sends.show', $send) }}"
                                        class="inline-flex"
                                    >
                                        <button
                                            type="button"
                                            @click="copy"
                                            class="inline-flex cursor-pointer border-2 border-zinc-950 p-2 text-zinc-700 dark:border-zinc-100 dark:text-zinc-300"
                                            title="{{ __('Copy link') }}"
                                        >
                                            <flux:icon.document-duplicate x-show="!copied" variant="outline" class="size-4" />
                                            <flux:icon.check x-show="copied" variant="solid" class="size-4 text-emerald-500" />
                                        </button>
                                    </span>

                                    @can('forceDelete', $send)
                                        <flux:modal.trigger name="delete-send-{{ $send->id }}">
                                            <button
                                                type="button"
                                                class="inline-flex cursor-pointer border-2 border-zinc-950 p-2 text-zinc-700 hover:border-red-600 hover:text-red-600 dark:border-zinc-100 dark:text-zinc-300 dark:hover:border-red-400 dark:hover:text-red-400"
                                                title="{{ __('Delete') }}"
                                            >
                                                <flux:icon.trash variant="outline" class="size-4" />
                                            </button>
                                        </flux:modal.trigger>
                                    @endcan
                                </div>
                            </div>

                            <dl class="mt-4 grid gap-3 border-t-2 border-zinc-950 pt-4 dark:border-zinc-100">
                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                                        {{ __('Expires') }}
                                    </dt>
                                    <dd class="mt-1 text-sm tabular-nums text-zinc-800 dark:text-zinc-200">
                                        <span
                                            x-data="localDatetime"
                                            data-utc-datetime="{{ \Illuminate\Support\Carbon::parse($send->valid_to)->utc()->toIso8601String() }}"
                                            x-text="formatted"
                                        ></span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">
                                        {{ __('Viewers') }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-zinc-800 dark:text-zinc-200">
                                        {{ $send->authorizedUsers->pluck('name')->join(', ') ?: __('None') }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4">
                                <flux:link
                                    :href="route('sends.show', $send)"
                                    wire:navigate
                                    class="inline-flex !rounded-none !border-2 !border-zinc-950 !px-4 !py-2 !text-xs !font-bold !uppercase !tracking-[0.16em] !text-zinc-950 hover:!bg-zinc-200 dark:!border-zinc-100 dark:!text-zinc-50 dark:hover:!bg-zinc-800"
                                >
                                    {{ __('View') }}
                                </flux:link>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            @foreach ($sends as $send)
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
                                    {{ __('Are you sure you want to delete this send?') }}
                                </flux:heading>

                                <flux:text class="!font-mono !text-sm">
                                    {{ __('This action cannot be undone.') }}
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
                                    {{ __('Yes') }}
                                </flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endcan
            @endforeach
        @endif
    </div>
</x-layouts::app>
