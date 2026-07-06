import { beforeEach, describe, expect, it, vi } from 'vitest';

const identityStore = vi.hoisted(() => ({
    value: null,
}));

vi.mock('./identitySession.js', () => ({
    getSessionBrowserDbId: vi.fn(() => '01JABCDEF1234567890ABCDEFGH'),
    getSessionPassword: vi.fn(() => 'secret-password'),
    loadIdentity: vi.fn(async () => identityStore.value),
    persistIdentity: vi.fn(async (_browserDbId, _password, identity) => {
        identityStore.value = identity;
    }),
    saveIdentity: vi.fn(async (identity) => {
        identityStore.value = identity;
    }),
    resolveBrowserDbId: vi.fn(() => '01JABCDEF1234567890ABCDEFGH'),
    setSessionBrowserDbId: vi.fn(),
    clearCachedIdentity: vi.fn(async () => {
        identityStore.value = null;
    }),
}));

vi.mock('./identityOverwrite.js', () => ({
    ensureIdentityOverwriteAllowed: vi.fn(async () => {}),
    IdentityKeyOverwriteCancelledError: class IdentityKeyOverwriteCancelledError extends Error {},
}));

vi.mock('./identity.js', async (importOriginal) => {
    const actual = await importOriginal();

    return {
        ...actual,
        registerPublicKey: vi.fn(async () => ({
            publicJwk: { kty: 'EC', crv: 'P-256', x: 'local-x', y: 'local-y' },
            fingerprint: 'a'.repeat(64),
        })),
    };
});

import { establishSession } from './session.js';
import { registerPublicKey } from './identity.js';

describe('session', () => {
    beforeEach(() => {
        identityStore.value = null;
        vi.clearAllMocks();
    });

    it('establishes an encrypted session with a remote public key', async () => {
        const remote = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const remoteJwk = await globalThis.crypto.subtle.exportKey('jwk', remote.publicKey);

        vi.stubGlobal('fetch', vi.fn(async () => ({
            ok: true,
            json: async () => ({
                public_key_jwk: remoteJwk,
                fingerprint: 'b'.repeat(64),
            }),
        })));

        const session = await establishSession({
            localUserId: 10,
            recipientId: 20,
            publicKeyUrl: '/api/users/20/public-key',
            registerUrl: '/api/identity/public-key',
            csrfToken: 'csrf-token',
        });

        expect(registerPublicKey).toHaveBeenCalledOnce();
        expect(session.partnerFingerprint).toBe('b'.repeat(64));
        expect(session.conversationKey.type).toBe('secret');
        expect(session.conversationKey.algorithm.name).toBe('AES-GCM');

        vi.unstubAllGlobals();
    });

    it('throws when the recipient public key is unavailable', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => ({ ok: false })));

        await expect(establishSession({
            localUserId: 1,
            recipientId: 2,
            publicKeyUrl: '/api/users/2/public-key',
            registerUrl: '/api/identity/public-key',
            csrfToken: 'csrf-token',
        })).rejects.toThrow('Recipient has not registered an encryption key yet.');

        vi.unstubAllGlobals();
    });
});
