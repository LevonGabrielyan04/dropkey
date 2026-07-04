import { beforeEach, describe, expect, it, vi } from 'vitest';
import { conversationInfo, deriveConversationKey } from './conversationKey.js';
import { importPublicKey } from './identity.js';

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
async function generateEcdhKeyPair() {
    const keyPair = await globalThis.crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        true,
        ['deriveBits', 'deriveKey'],
    );

    const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

    return {
        privateKey: keyPair.privateKey,
        publicKey: keyPair.publicKey,
        publicJwk,
    };
}

describe('conversationKey', () => {
    it('uses canonical user ordering in the info string', () => {
        expect(conversationInfo(5, 10)).toBe('passshare:conv:5:10');
        expect(conversationInfo(10, 5)).toBe('passshare:conv:5:10');
    });

    it('derives the same key for both conversation participants', async () => {
        const alice = await generateEcdhKeyPair();
        const bob = await generateEcdhKeyPair();
        const bobPublic = await importPublicKey(bob.publicJwk);
        const alicePublic = await importPublicKey(alice.publicJwk);

        const aliceKey = await deriveConversationKey(alice.privateKey, bobPublic, 1, 2);
        const bobKey = await deriveConversationKey(bob.privateKey, alicePublic, 2, 1);

        const iv = globalThis.crypto.getRandomValues(new Uint8Array(12));
        const ciphertext = await globalThis.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            aliceKey,
            new TextEncoder().encode('pairwise-secret'),
        );

        const plaintext = await globalThis.crypto.subtle.decrypt(
            { name: 'AES-GCM', iv },
            bobKey,
            ciphertext,
        );

        expect(new TextDecoder().decode(plaintext)).toBe('pairwise-secret');
    });
});

describe('identity', () => {
    beforeEach(() => {
        vi.resetModules();
    });

    it('imports a remote ECDH public JWK', async () => {
        const bob = await generateEcdhKeyPair();
        const imported = await importPublicKey(bob.publicJwk);

        expect(imported.type).toBe('public');
        expect(imported.algorithm.name).toBe('ECDH');
    });
});
