import {
    decryptChatMessage,
    encryptChatMessage,
    establishSession,
} from './cryptography/e2ee/session.js';

/**
 * Alpine component for a 1v1 E2EE chat session.
 * All crypto runs in the browser via Web Crypto; the server relays ciphertext only.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('e2eeChatSession', () => ({
        loading: true,
        ready: false,
        error: '',
        messages: [],
        messageText: '',
        sending: false,
        sendError: '',
        partnerFingerprint: '',
        conversationKey: null,
        lastMessageId: 0,
        pollTimer: null,
        localUserId: 0,
        recipientId: 0,
        csrfToken: '',
        messagesUrl: '',
        sendUrl: '',
        registerUrl: '',
        publicKeyUrl: '',
        pollIntervalMs: 3000,

        init() {
            this.localUserId = Number(this.$el.dataset.localUserId);
            this.recipientId = Number(this.$el.dataset.recipientId);
            this.csrfToken = this.$el.dataset.csrfToken ?? '';
            this.messagesUrl = this.$el.dataset.messagesUrl ?? '';
            this.sendUrl = this.$el.dataset.sendUrl ?? '';
            this.registerUrl = this.$el.dataset.registerUrl ?? '';
            this.publicKeyUrl = this.$el.dataset.publicKeyUrl ?? '';
            this.pollIntervalMs = Number(this.$el.dataset.pollIntervalMs) || 3000;

            this._visibilityHandler = () => {
                if (document.visibilityState === 'hidden') {
                    this.stopPolling();
                } else if (this.ready) {
                    this.fetchMessages();
                    this.startPolling();
                }
            };

            document.addEventListener('visibilitychange', this._visibilityHandler);

            this.bootstrap();
        },

        destroy() {
            this.stopPolling();
            document.removeEventListener('visibilitychange', this._visibilityHandler);
        },

        async bootstrap() {
            this.loading = true;
            this.error = '';
            this.ready = false;

            try {
                const session = await establishSession({
                    localUserId: this.localUserId,
                    recipientId: this.recipientId,
                    publicKeyUrl: this.publicKeyUrl,
                    registerUrl: this.registerUrl,
                    csrfToken: this.csrfToken,
                });

                this.conversationKey = session.conversationKey;
                this.partnerFingerprint = session.partnerFingerprint;

                await this.fetchMessages();
                this.startPolling();
                this.ready = true;
            } catch {
                this.error = 'Unable to establish an encrypted session. Ensure your partner has opened Messages at least once.';
            } finally {
                this.loading = false;
            }
        },

        async fetchMessages() {
            if (! this.conversationKey || ! this.messagesUrl) {
                return;
            }

            const url = this.lastMessageId > 0
                ? `${this.messagesUrl}?after_id=${this.lastMessageId}`
                : this.messagesUrl;

            let response;

            try {
                response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
            } catch {
                return;
            }

            if (! response.ok) {
                return;
            }

            const data = await response.json();
            const incoming = Array.isArray(data.messages) ? data.messages : [];

            for (const message of incoming) {
                await this.ingestMessage(message);
            }
        },

        async ingestMessage(message) {
            if (this.messages.some((existing) => existing.id === message.id)) {
                return;
            }

            let plaintext = '';

            try {
                plaintext = await decryptChatMessage(message.payload, this.conversationKey);
            } catch {
                plaintext = '[Unable to decrypt message]';
            }

            this.messages.push({
                id: message.id,
                senderId: message.sender_id,
                plaintext,
                createdAt: message.created_at,
                isMine: message.sender_id === this.localUserId,
            });

            this.lastMessageId = Math.max(this.lastMessageId, message.id);
            this.sortMessages();
            this.scrollToBottom();
        },

        sortMessages() {
            this.messages.sort((left, right) => left.id - right.id);
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messageList;

                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        startPolling() {
            this.stopPolling();

            this.pollTimer = window.setInterval(() => {
                this.fetchMessages();
            }, this.pollIntervalMs);
        },

        stopPolling() {
            if (this.pollTimer !== null) {
                window.clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        async sendMessage() {
            const text = this.messageText.trim();

            if (! this.ready || this.sending || text === '' || ! this.conversationKey) {
                return;
            }

            this.sending = true;
            this.sendError = '';

            try {
                const payload = await encryptChatMessage(text, this.conversationKey);

                const response = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        recipient_id: this.recipientId,
                        payload,
                    }),
                });

                if (! response.ok) {
                    this.sendError = 'Failed to send encrypted message.';
                    return;
                }

                const created = await response.json();

                this.messages.push({
                    id: created.id,
                    senderId: this.localUserId,
                    plaintext: text,
                    createdAt: created.created_at,
                    isMine: true,
                });

                this.lastMessageId = Math.max(this.lastMessageId, created.id);
                this.messageText = '';
                this.sortMessages();
                this.scrollToBottom();
            } catch {
                this.sendError = 'Encryption or delivery failed. Try again.';
            } finally {
                this.sending = false;
            }
        },
    }));

    Alpine.data('e2eeChatInbox', () => ({
        username: '',
        error: '',

        init() {
            this.chatBaseUrl = this.$el.dataset.chatBaseUrl ?? '/chat';
        },

        startChat() {
            this.error = '';
            const name = this.username.trim().replace(/,/g, '');

            if (! name) {
                return;
            }

            if (name.length > 255) {
                this.error = 'User name must be 255 characters or fewer.';
                return;
            }

            const segment = encodeURIComponent(name);

            if (window.Livewire?.navigate) {
                window.Livewire.navigate(`${this.chatBaseUrl}/${segment}`);
            } else {
                window.location.href = `${this.chatBaseUrl}/${segment}`;
            }
        },
    }));
});
