import { decryptViaWorker } from './cryptography/decrypt/decryptViaWorker.js';
import { parseEncryptedPayload } from './cryptography/core/parseEncryptedPayload.js';

/**
 * Alpine component for viewing a Send, decrypting password-protected
 * messages off-thread via decryptViaWorker.js.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('sendDetailsManager', () => ({
        rawMessage: '',
        decryptedMessage: null,
        password: '',
        passwordError: '',
        decryptionError: '',
        isDecrypting: false,
        minPasswordLength: 15,

        init() {
            const raw = this.$el.dataset.rawMessage;

            if (raw) {
                try {
                    this.rawMessage = JSON.parse(raw);
                } catch {
                    this.rawMessage = raw;
                }
            }

            const minLength = Number(this.$el.dataset.minPasswordLength);

            if (! Number.isNaN(minLength) && minLength > 0) {
                this.minPasswordLength = minLength;
            }
        },

        get isEncrypted() {
            return parseEncryptedPayload(this.rawMessage) !== null;
        },

        get displayMessage() {
            if (this.decryptedMessage !== null) {
                return this.decryptedMessage;
            }

            if (! this.isEncrypted) {
                return this.rawMessage;
            }

            return null;
        },

        setPassword(event) {
            this.password = event.target.value;
            this.passwordError = '';
        },

        async decrypt() {
            if (this.isDecrypting || ! this.isEncrypted) {
                return;
            }

            const password = this.password.trim();

            this.passwordError = '';
            this.decryptionError = '';

            if (! password) {
                this.passwordError = 'Password is required to decrypt this message.';

                return;
            }

            if (password.length < this.minPasswordLength) {
                this.passwordError = `Password must be at least ${this.minPasswordLength} characters.`;

                return;
            }

            this.isDecrypting = true;

            try {
                const encryptedObj = parseEncryptedPayload(this.rawMessage);
                this.decryptedMessage = await decryptViaWorker(encryptedObj, password);
                this.password = '';
            } catch {
                this.decryptionError = 'Decryption failed. Check your password and try again.';
            } finally {
                this.isDecrypting = false;
            }
        },
    }));
});
