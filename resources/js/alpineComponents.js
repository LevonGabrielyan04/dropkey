import { formatLocalDatetime } from './formatLocalDatetime.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('navigation', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        get responsiveMenuClasses() {
            return {
                block: this.open,
                hidden: !this.open,
            };
        },

        get hamburgerClosedIconClasses() {
            return {
                hidden: this.open,
                'inline-flex': !this.open,
            };
        },

        get hamburgerOpenIconClasses() {
            return {
                hidden: !this.open,
                'inline-flex': this.open,
            };
        },
    }));

    Alpine.data('dropdown', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },
    }));

    Alpine.data('modal', (initialShow = false, focusable = false, name = '') => ({
        show: initialShow,
        modalName: name,
        focusable,

        init() {
            this.$watch('show', (value) => {
                if (value) {
                    document.body.classList.add('overflow-y-hidden');

                    if (this.focusable) {
                        setTimeout(() => this.firstFocusable()?.focus(), 100);
                    }
                } else {
                    document.body.classList.remove('overflow-y-hidden');
                }
            });
        },

        focusables() {
            const selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';

            return [...this.$el.querySelectorAll(selector)]
                .filter((element) => !element.hasAttribute('disabled'));
        },

        firstFocusable() {
            return this.focusables()[0];
        },

        lastFocusable() {
            return this.focusables().slice(-1)[0];
        },

        nextFocusable() {
            return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable();
        },

        prevFocusable() {
            return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable();
        },

        nextFocusableIndex() {
            return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1);
        },

        prevFocusableIndex() {
            return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1;
        },

        handleOpenModal(event) {
            if (event.detail === this.modalName) {
                this.show = true;
            }
        },

        handleCloseModal(event) {
            if (event.detail === this.modalName) {
                this.show = false;
            }
        },

        close() {
            this.show = false;
        },

        handleTabKey(event) {
            if (!event.shiftKey) {
                this.nextFocusable().focus();
            }
        },

        handleShiftTabKey() {
            this.prevFocusable().focus();
        },
    }));

    Alpine.data('recoveryCodesVisibility', () => ({
        showRecoveryCodes: false,

        show() {
            this.showRecoveryCodes = true;
        },

        hide() {
            this.showRecoveryCodes = false;
        },
    }));

    Alpine.data('savedMessage', () => ({
        show: true,

        init() {
            setTimeout(() => {
                this.show = false;
            }, 2000);
        },
    }));

    Alpine.data('passwordVisibility', () => ({
        showPassword: false,

        toggle() {
            this.showPassword = ! this.showPassword;
        },
    }));

    Alpine.data('passwordTooltip', () => ({
        showTooltip: false,

        show() {
            this.showTooltip = true;
        },

        hide() {
            this.showTooltip = false;
        },
    }));

    Alpine.data('dispatchOnClick', (eventName, detail = null) => ({
        handleClick() {
            this.$dispatch(eventName, detail);
        },
    }));

    Alpine.data('twoFactorChallenge', () => ({
        showRecoveryInput: false,
        code: '',
        recovery_code: '',

        init() {
            this.showRecoveryInput = this.$el.dataset.showRecoveryInput === 'true';

            if (! this.showRecoveryInput) {
                this.focusOtp();
            }
        },

        focusOtp() {
            this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
        },

        toggleInput() {
            this.showRecoveryInput = ! this.showRecoveryInput;

            this.code = '';
            this.recovery_code = '';

            this.$nextTick(() => {
                if (this.showRecoveryInput) {
                    this.$refs.recovery_code?.focus();
                } else {
                    this.focusOtp();
                }
            });
        },
    }));

    Alpine.data('localDatetime', () => ({
        formatted: '',

        init() {
            const utcDatetime = this.$el.dataset.utcDatetime ?? '';

            this.formatted = formatLocalDatetime(utcDatetime);
        },
    }));

    Alpine.data('copyText', () => ({
        copied: false,
        text: '',

        init() {
            this.text = this.$el.dataset.copyText ?? '';
        },

        async copy() {
            if (! this.text) {
                return;
            }

            try {
                await navigator.clipboard.writeText(this.text);
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 1500);
            } catch {
                console.warn('Could not copy to clipboard');
            }
        },
    }));

    Alpine.data('setupKeyCopy', () => ({
        copied: false,
        setupKey: '',

        init() {
            this.setupKey = this.$el.dataset.setupKey ?? '';
        },

        async copy() {
            if (! this.setupKey) {
                return;
            }

            try {
                await navigator.clipboard.writeText(this.setupKey);
                this.copied = true;
                setTimeout(() => {
                    this.copied = false;
                }, 1500);
            } catch {
                console.warn('Could not copy to clipboard');
            }
        },
    }));
});
