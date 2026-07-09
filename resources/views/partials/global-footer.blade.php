@php
    $nested = $nested ?? false;
@endphp

<div
    @class([
        'global-footer hidden border-t-2 border-zinc-950 bg-zinc-100 px-4 py-3 font-mono dark:border-zinc-100 dark:bg-zinc-900 lg:block',
        'fixed inset-x-0 bottom-0 z-40' => ! $nested,
        'lg:static lg:inset-x-auto lg:bottom-auto' => ! $nested,
        'lg:static lg:inset-x-auto lg:bottom-auto lg:border-t-2 lg:bg-transparent lg:px-0 lg:py-0 lg:pt-8 dark:lg:bg-transparent' => $nested,
    ])
    @unless($nested)
        role="contentinfo"
    @endunless
    data-test="global-footer"
>
    @include('partials.global-footer-content')
</div>
