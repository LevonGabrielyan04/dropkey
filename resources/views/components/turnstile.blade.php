@if (config('turnstile.enabled'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer nonce="{{ Illuminate\Support\Facades\Vite::cspNonce() }}"></script>

    <div
        x-data="{
            init() {
                this.$nextTick(() => this.mountTurnstile());
            },
            mountTurnstile() {
                const sitekey = @js(config('turnstile.site_key'));

                const attemptRender = () => {
                    if (! window.turnstile?.render) {
                        setTimeout(attemptRender, 50);

                        return;
                    }

                    window.turnstile.render(this.$refs.widget, {
                        sitekey,
                        theme: 'auto',
                        callback: () => this.$dispatch('turnstile-verified'),
                        'expired-callback': () => this.$dispatch('turnstile-expired'),
                        'error-callback': () => this.$dispatch('turnstile-error'),
                    });
                };

                attemptRender();
            },
        }"
    >
        <div x-ref="widget"></div>

        @error('cf-turnstile-response')
            <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror
    </div>
@endif
