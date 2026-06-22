import { encryptViaWorker } from './cryptography/encrypt/encryptViaWorker.js';

/**
 * Alpine component that manages the list of viewer email addresses
 * for a Send, handling client-side validation and de-duplication.
 *
 * Registered globally so both the create and edit forms can reuse it
 * via `x-data="viewerManager(initialViewers)"`.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('viewerManager', () => ({
        viewers: [],
        newViewer: '',
        error: '',
        passwordError: '',
        encryptionError: '',
        isEncrypting: false,
        maxViewers: 100,
        minPasswordLength: 15,

        init() {
            const initialViewers = this.$el.dataset.initialViewers;

            if (initialViewers) {
                try {
                    this.viewers = JSON.parse(initialViewers);
                } catch {
                    this.viewers = [];
                }
            }

            const minLength = Number(this.$el.dataset.minPasswordLength);

            if (! Number.isNaN(minLength) && minLength > 0) {
                this.minPasswordLength = minLength;
            }
        },

        addViewer() {
            this.error = '';
            const email = this.newViewer.trim().replace(',', '');

            if (!email) {
                return;
            }

            // Basic email validation regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.error = 'Please enter a valid email address.';
                return;
            }

            if (this.viewers.includes(email)) {
                this.error = 'This email has already been added.';
                return;
            }

            if (this.viewers.length >= this.maxViewers) {
                this.error = `You can only add up to ${this.maxViewers} viewers.`;
                return;
            }

            this.viewers.push(email);
            this.newViewer = '';
        },

        removeViewer(index) {
            this.viewers.splice(index, 1);
            this.error = '';
        },

        removeViewerFromEvent(event) {
            const index = Number(event.currentTarget.dataset.index);

            this.removeViewer(index);
        },

        setNewViewer(event) {
            this.newViewer = event.target.value;
        },

        clearPasswordError() {
            this.passwordError = '';
        },

        get isViewerInputRequired() {
            return this.viewers.length === 0;
        },

        get hasViewers() {
            return this.viewers.length > 0;
        },

        /**
         * Strip Alpine directives from a cloned form so Livewire's Alpine
         * does not re-initialize it and evaluate x-for loop variables such
         * as `email` outside their scope.
         */
        prepareClonedForm(form) {
            form.querySelectorAll('template').forEach((template) => template.remove());

            form.querySelectorAll('[name="viewers[]"]').forEach((input) => input.remove());

            this.viewers.forEach((viewer) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'viewers[]';
                input.value = viewer;
                form.appendChild(input);
            });

            form.querySelectorAll('*').forEach((element) => {
                [...element.attributes].forEach((attribute) => {
                    const { name } = attribute;

                    if (name.startsWith('x-') || name.startsWith('@') || name.startsWith(':')) {
                        element.removeAttribute(name);
                    }
                });
            });

            form.removeAttribute('x-data');
            form.removeAttribute('@submit.prevent');
            form.setAttribute('x-ignore', '');
        },

        /**
         * Handles form submission. Pending viewer emails are committed first,
         * then the message is optionally encrypted off-thread before the cloned
         * form is submitted without the password field.
         */
        async submitForm(event) {
            if (this.isEncrypting) {
                return;
            }

            if (this.newViewer.trim()) {
                this.addViewer();

                if (this.error) {
                    return;
                }
            }

            await this.$nextTick();

            const form = event.target.cloneNode(true);
            form.noValidate = true;
            this.prepareClonedForm(form);

            const messageInput = form.querySelector('[name="message"]');
            const passwordInput = form.querySelector('[name="password"]');

            const plaintext = messageInput?.value ?? '';
            const password = passwordInput?.value ?? '';
            let message = plaintext;

            this.passwordError = '';
            this.encryptionError = '';

            if (password && password.length < this.minPasswordLength) {
                this.passwordError = `Password must be at least ${this.minPasswordLength} characters.`;

                return;
            }

            if (password) {
                this.isEncrypting = true;

                try {
                    const encrypted = await encryptViaWorker(plaintext, password);
                    message = JSON.stringify(encrypted);
                } catch {
                    this.encryptionError = 'Encryption failed. Your message was not sent. Please try again.';

                    return;
                } finally {
                    this.isEncrypting = false;
                }
            }

            if (messageInput) {
                messageInput.removeAttribute('maxlength');
                messageInput.value = message;
            }

            passwordInput?.remove();

            form.style.display = 'none';
            document.body.appendChild(form);
            form.submit();
        },
    }));
});
