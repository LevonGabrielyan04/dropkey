import {
    decryptChatMessage,
    encryptChatMessage,
    establishSession,
} from './cryptography/e2ee/session.js';

export const DEFAULT_AUTO_DELETE = '7 days';

/**
 * @param {string} payload
 * @param {CryptoKey} conversationKey
 * @param {string} decryptionFailedMessage
 * @returns {Promise<{ plaintext: string|null, decryptionError: string }>}
 */
export async function resolveChatMessageContent(payload, conversationKey, decryptionFailedMessage) {
    try {
        const plaintext = await decryptChatMessage(payload, conversationKey);

        return { plaintext, decryptionError: '' };
    } catch {
        return { plaintext: null, decryptionError: decryptionFailedMessage };
    }
}

/**
 * Alpine component for a 1v1 E2EE chat session.
 * All crypto runs in the browser via Web Crypto; the server relays ciphertext only.
 */
if (typeof document !== 'undefined') {
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
        lastMessagePublicId: '',
        pollTimer: null,
        localUserId: 0,
        localUserPublicId: '',
        recipientId: 0,
        csrfToken: '',
        messagesUrl: '',
        sendUrl: '',
        registerUrl: '',
        mineUrl: '',
        publicKeyUrl: '',
        pollIntervalMs: 3000,
        decryptionFailedMessage: 'Unable to decrypt this message.',
        autoDelete: DEFAULT_AUTO_DELETE,
        autoDeleteUrl: '',
        autoDeleteSaving: false,
        autoDeleteError: '',

        get canSendMessage() {
            return this.ready && ! this.sending && this.messageText.trim() !== '';
        },

        init() {
            this.localUserId = Number(this.$el.dataset.localUserId);
            this.localUserPublicId = this.$el.dataset.localUserPublicId ?? '';
            this.recipientId = Number(this.$el.dataset.recipientId);
            this.csrfToken = this.$el.dataset.csrfToken ?? '';
            this.messagesUrl = this.$el.dataset.messagesUrl ?? '';
            this.sendUrl = this.$el.dataset.sendUrl ?? '';
            this.registerUrl = this.$el.dataset.registerUrl ?? '';
            this.mineUrl = this.$el.dataset.mineUrl ?? '';
            this.publicKeyUrl = this.$el.dataset.publicKeyUrl ?? '';
            this.pollIntervalMs = Number(this.$el.dataset.pollIntervalMs) || 3000;
            this.decryptionFailedMessage = this.$el.dataset.decryptionFailedMessage
                ?? 'Unable to decrypt this message.';
            this.autoDelete = this.$el.dataset.autoDelete || DEFAULT_AUTO_DELETE;
            this.autoDeleteUrl = this.$el.dataset.autoDeleteUrl ?? '';

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
                    mineUrl: this.mineUrl,
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

            const url = this.lastMessagePublicId !== ''
                ? `${this.messagesUrl}?after_public_id=${encodeURIComponent(this.lastMessagePublicId)}`
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

            if (incoming.length > 0) {
                this.lastMessagePublicId = incoming[incoming.length - 1].public_id;
            }
        },

        async ingestMessage(message) {
            if (this.messages.some((existing) => existing.publicId === message.public_id)) {
                return;
            }

            const { plaintext, decryptionError } = await resolveChatMessageContent(
                message.payload,
                this.conversationKey,
                this.decryptionFailedMessage,
            );

            this.messages.push({
                publicId: message.public_id,
                senderPublicId: message.sender.public_id,
                plaintext,
                decryptionError,
                createdAt: message.created_at,
                isMine: message.sender.public_id === this.localUserPublicId,
            });

            this.sortMessages();
            this.scrollToBottom();
        },

        sortMessages() {
            this.messages.sort((left, right) => new Date(left.createdAt) - new Date(right.createdAt));
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

        async updateAutoDelete() {
            if (! this.autoDeleteUrl || this.autoDeleteSaving) {
                return;
            }

            const previousAutoDelete = this.$el.dataset.autoDelete || DEFAULT_AUTO_DELETE;
            this.autoDeleteSaving = true;
            this.autoDeleteError = '';

            try {
                const response = await fetch(this.autoDeleteUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        auto_delete: this.autoDelete,
                    }),
                });

                if (! response.ok) {
                    this.autoDelete = previousAutoDelete;
                    this.autoDeleteError = 'Failed to update auto-delete setting.';
                    return;
                }

                const data = await response.json();
                this.autoDelete = data.auto_delete ?? this.autoDelete;
                this.$el.dataset.autoDelete = this.autoDelete;
            } catch {
                this.autoDelete = previousAutoDelete;
                this.autoDeleteError = 'Failed to update auto-delete setting.';
            } finally {
                this.autoDeleteSaving = false;
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
                    publicId: created.public_id,
                    senderPublicId: this.localUserPublicId,
                    plaintext: text,
                    decryptionError: '',
                    createdAt: created.created_at,
                    isMine: true,
                });

                this.lastMessagePublicId = created.public_id;
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
}
