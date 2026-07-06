import { beforeEach, describe, expect, it, vi } from 'vitest';

const encryptedStore = vi.hoisted(() => ({
    value: null,
}));

const encryptViaWorker = vi.hoisted(() => vi.fn(async (plaintext, password) => ({
    ciphertext: `encrypted:${plaintext}`,
    salt: 'salt',
    iv: 'iv',
    password,
})));

const decryptViaWorker = vi.hoisted(() => vi.fn(async (encrypted, password) => {
    if (! encrypted.ciphertext.startsWith('encrypted:')) {
        throw new Error('Invalid ciphertext.');
    }

    return encrypted.ciphertext.slice('encrypted:'.length);
}));

vi.mock('../encrypt/encryptViaWorker.js', () => ({
    encryptViaWorker,
}));

vi.mock('../decrypt/decryptViaWorker.js', () => ({
    decryptViaWorker,
}));

vi.mock('./identitySerialization.js', async (importOriginal) => {
    const actual = await importOriginal();

    return {
        ...actual,
    };
});

vi.mock('./keyStore.js', () => ({
    databaseNameForBrowserDbId: vi.fn((browserDbId) => `passshare-${browserDbId}`),
    loadEncryptedIdentity: vi.fn(async () => encryptedStore.value),
    saveEncryptedIdentity: vi.fn(async (_browserDbId, payload) => {
        encryptedStore.value = payload;
    }),
}));

import {
    clearSessionCredentials,
    getCachedIdentity,
    getSessionPassword,
    loadIdentity,
    persistIdentity,
    saveIdentity,
    setSessionBrowserDbId,
    setSessionPassword,
    unlockIdentity,
} from './identitySession.js';

describe('identitySession', () => {
    beforeEach(() => {
        encryptedStore.value = null;
        clearSessionCredentials();
        vi.clearAllMocks();
    });

    it('stores and reads the session password', () => {
        setSessionPassword('secret-password');

        expect(getSessionPassword()).toBe('secret-password');
    });

    it('clears cached identity and session credentials', async () => {
        setSessionPassword('secret-password');

        const keyPair = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

        await persistIdentity('01JABCDEF1234567890ABCDEFGH', 'secret-password', {
            privateKey: keyPair.privateKey,
            publicJwk,
        });

        clearSessionCredentials();

        expect(getSessionPassword()).toBeNull();
        expect(getCachedIdentity()).toBeNull();
    });

    it('decrypts and caches identity from encrypted storage', async () => {
        const keyPair = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

        await persistIdentity('01JABCDEF1234567890ABCDEFGH', 'secret-password', {
            privateKey: keyPair.privateKey,
            publicJwk,
        });

        clearSessionCredentials();
        encryptedStore.value = {
            ciphertext: encryptedStore.value.ciphertext,
            salt: 'salt',
            iv: 'iv',
        };

        const unlocked = await unlockIdentity('01JABCDEF1234567890ABCDEFGH', 'secret-password');

        expect(unlocked?.publicJwk).toEqual(publicJwk);
        expect(getCachedIdentity()?.publicJwk).toEqual(publicJwk);
        expect(decryptViaWorker).toHaveBeenCalledOnce();
    });

    it('returns null when no encrypted identity exists', async () => {
        const unlocked = await unlockIdentity('01JABCDEF1234567890ABCDEFGH', 'secret-password');

        expect(unlocked).toBeNull();
        expect(getCachedIdentity()).toBeNull();
        expect(decryptViaWorker).not.toHaveBeenCalled();
    });

    it('loads identity from cache without hitting storage again', async () => {
        const keyPair = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

        await persistIdentity('01JABCDEF1234567890ABCDEFGH', 'secret-password', {
            privateKey: keyPair.privateKey,
            publicJwk,
        });

        const { loadEncryptedIdentity } = await import('./keyStore.js');
        vi.mocked(loadEncryptedIdentity).mockClear();

        const loaded = await loadIdentity();

        expect(loaded?.publicJwk).toEqual(publicJwk);
        expect(loadEncryptedIdentity).not.toHaveBeenCalled();
    });

    it('throws when saving without session credentials', async () => {
        const keyPair = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

        vi.stubGlobal('document', {
            body: {
                dataset: {},
            },
        });

        await expect(saveIdentity({
            privateKey: keyPair.privateKey,
            publicJwk,
        })).rejects.toThrow('Cannot save identity without a browser database id and session password.');

        vi.unstubAllGlobals();
    });

    it('resolves the browser database id from the document dataset', async () => {
        setSessionPassword('secret-password');

        const keyPair = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

        vi.stubGlobal('document', {
            body: {
                dataset: {
                    browserDbId: '01JABCDEF1234567890ABCDEFGH',
                },
            },
        });

        await saveIdentity({
            privateKey: keyPair.privateKey,
            publicJwk,
        });

        const { saveEncryptedIdentity } = await import('./keyStore.js');

        expect(saveEncryptedIdentity).toHaveBeenCalledWith(
            '01JABCDEF1234567890ABCDEFGH',
            expect.objectContaining({
                ciphertext: expect.stringContaining('encrypted:'),
            }),
        );

        vi.unstubAllGlobals();
    });

    it('uses the session browser database id when the document dataset is empty', async () => {
        setSessionBrowserDbId('01JABCDEF1234567890ABCDEFGH');
        setSessionPassword('secret-password');

        vi.stubGlobal('document', {
            body: {
                dataset: {},
            },
        });

        await expect(loadIdentity()).resolves.toBeNull();

        vi.unstubAllGlobals();
    });
});
