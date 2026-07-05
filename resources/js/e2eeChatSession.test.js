import { beforeEach, describe, expect, it, vi } from 'vitest';

const decryptChatMessage = vi.hoisted(() => vi.fn());

vi.mock('./cryptography/e2ee/session.js', () => ({
    decryptChatMessage,
    encryptChatMessage: vi.fn(),
    establishSession: vi.fn(),
}));

import { resolveChatMessageContent } from './e2eeChatSession.js';

describe('resolveChatMessageContent', () => {
    beforeEach(() => {
        decryptChatMessage.mockReset();
    });

    it('returns plaintext when decryption succeeds', async () => {
        decryptChatMessage.mockResolvedValue('Hello');

        const result = await resolveChatMessageContent('payload', {}, 'Unable to decrypt this message.');

        expect(result).toEqual({
            plaintext: 'Hello',
            decryptionError: '',
        });
    });

    it('returns a decryption error when decryption fails', async () => {
        decryptChatMessage.mockRejectedValue(new Error('OperationError'));

        const result = await resolveChatMessageContent('payload', {}, 'Unable to decrypt this message.');

        expect(result).toEqual({
            plaintext: null,
            decryptionError: 'Unable to decrypt this message.',
        });
    });
});
