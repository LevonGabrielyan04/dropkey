import { describe, expect, it } from 'vitest';
import { deriveConversationKey } from './conversationKey.js';
import { importPublicKey } from './identity.js';
import {
    PAYLOAD_VERSION,
    decryptMessage,
    encryptMessage,
    parseChatPayload,
} from './messageCrypto.js';

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
async function generateEcdhKeyPair() {
    const keyPair = await globalThis.crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        true,
        ['deriveBits', 'deriveKey'],
    );

    return {
        privateKey: keyPair.privateKey,
        publicJwk: await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey),
    };
}

async function conversationKeyForPair(alice, bob, aliceId, bobId) {
    const bobPublic = await importPublicKey(bob.publicJwk);

    return deriveConversationKey(alice.privateKey, bobPublic, aliceId, bobId);
}

describe('messageCrypto', () => {
    it('encrypts and decrypts chat payloads', async () => {
        const alice = await generateEcdhKeyPair();
        const bob = await generateEcdhKeyPair();
        const conversationKey = await conversationKeyForPair(alice, bob, 1, 2);

        const payload = await encryptMessage('Hello, encrypted world.', conversationKey);
        const plaintext = await decryptMessage(payload, conversationKey);

        expect(plaintext).toBe('Hello, encrypted world.');
    });

    it('produces backend-compatible payload shape', async () => {
        const alice = await generateEcdhKeyPair();
        const bob = await generateEcdhKeyPair();
        const conversationKey = await conversationKeyForPair(alice, bob, 3, 4);

        const payload = await encryptMessage('shape-check', conversationKey);
        const parsed = parseChatPayload(payload);

        expect(parsed).not.toBeNull();
        expect(parsed.v).toBe(PAYLOAD_VERSION);
        expect(typeof parsed.ciphertext).toBe('string');
        expect(typeof parsed.iv).toBe('string');
        expect(atob(parsed.iv)).toHaveLength(12);
    });

    it('rejects unsupported payload versions', async () => {
        const alice = await generateEcdhKeyPair();
        const bob = await generateEcdhKeyPair();
        const conversationKey = await conversationKeyForPair(alice, bob, 5, 6);

        await expect(decryptMessage(JSON.stringify({
            v: 2,
            ciphertext: 'abc',
            iv: btoa(String.fromCharCode(...new Uint8Array(12).fill(1))),
        }), conversationKey)).rejects.toThrow('Unsupported message payload version.');
    });

    it('returns null for invalid payload shapes', () => {
        expect(parseChatPayload('not-json')).toBeNull();
        expect(parseChatPayload(JSON.stringify({ v: 1 }))).toBeNull();
        expect(parseChatPayload(JSON.stringify({
            v: 1,
            ciphertext: 'abc',
            salt: 'legacy-send-field',
        }))).toBeNull();
    });
});
