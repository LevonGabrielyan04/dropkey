import { Passkeys } from '@laravel/passkeys';

window.Passkeys = Passkeys;
window.dispatchEvent(new CustomEvent('passkeys:ready'));

document.addEventListener('alpine:init', () => {
    Alpine.data('passkeyVerify', () => ({
        supported: false,
        loading: false,
        error: null,
        optionsRoute: '',
        submitRoute: '',

        init() {
            this.optionsRoute = this.$el.dataset.optionsRoute ?? '';
            this.submitRoute = this.$el.dataset.submitRoute ?? '';
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },

        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },

        async verify() {
            this.loading = true;
            this.error = null;

            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: this.optionsRoute,
                        submit: this.submitRoute,
                    },
                });

                Livewire.navigate(response.redirect || '/dashboard');
            } catch (error) {
                if (error.constructor?.name !== 'UserCancelledError') {
                    this.error = error.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('passkeyRegistration', () => ({
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,

        get canRegister() {
            return ! this.loading && this.name.trim() !== '';
        },

        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },

        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },

        async register() {
            if (! this.name.trim()) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                this.name = '';
                this.showForm = false;
                await this.$wire.loadPasskeys();
            } catch (error) {
                if (error.constructor?.name !== 'UserCancelledError') {
                    this.error = error.message;
                }
            } finally {
                this.loading = false;
            }
        },

        cancel() {
            this.showForm = false;
            this.name = '';
            this.error = null;
        },
    }));
});
