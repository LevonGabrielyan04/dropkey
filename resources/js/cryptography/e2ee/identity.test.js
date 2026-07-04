import { beforeEach, describe, expect, it, vi } from 'vitest';

const identityStore = vi.hoisted(() => ({
    value: null,
}));

vi.mock('./keyStore.js', () => ({
    loadIdentity: vi.fn(async () => identityStore.value),
    saveIdentity: vi.fn(async (identity) => {
        identityStore.value = identity;
    }),
    clearIdentity: vi.fn(async () => {
        identityStore.value = null;
    }),
}));

import { clearIdentity, loadIdentity, saveIdentity } from './keyStore.js';
import { ensureIdentityKeyPair, registerPublicKey } from './identity.js';

describe('keyStore integration via identity', () => {
    beforeEach(async () => {
        identityStore.value = null;
        vi.clearAllMocks();
    });

    it('generates and persists an identity key pair', async () => {
        const first = await ensureIdentityKeyPair();
        const second = await ensureIdentityKeyPair();

        expect(first.fingerprint).toMatch(/^[a-f0-9]{64}$/);
        expect(first.publicJwk.kty).toBe('EC');
        expect(first.publicJwk.crv).toBe('P-256');
        expect(second.fingerprint).toBe(first.fingerprint);
        expect(saveIdentity).toHaveBeenCalledTimes(1);
        expect(loadIdentity).toHaveBeenCalled();
    });

    it('registers the public key with the relay', async () => {
        const fetchMock = vi.fn(async () => ({
            ok: true,
        }));
        vi.stubGlobal('fetch', fetchMock);

        await registerPublicKey('/api/identity/public-key', 'csrf-token');

        expect(fetchMock).toHaveBeenCalledOnce();
        expect(fetchMock.mock.calls[0][0]).toBe('/api/identity/public-key');

        const requestInit = fetchMock.mock.calls[0][1];
        const body = JSON.parse(requestInit.body);

        expect(requestInit.method).toBe('POST');
        expect(requestInit.headers['X-CSRF-TOKEN']).toBe('csrf-token');
        expect(body.public_key_jwk.kty).toBe('EC');
        expect(body.fingerprint).toMatch(/^[a-f0-9]{64}$/);
        expect(body).not.toHaveProperty('private_key');

        vi.unstubAllGlobals();
    });

    it('throws when public key registration fails', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => ({ ok: false })));

        await expect(registerPublicKey('/api/identity/public-key', 'csrf-token'))
            .rejects
            .toThrow('Failed to register public key.');

        vi.unstubAllGlobals();
    });

    it('reloads a stored identity without regenerating keys', async () => {
        const generated = await ensureIdentityKeyPair();

        await clearIdentity();
        identityStore.value = {
            privateKey: generated.privateKey,
            publicJwk: generated.publicJwk,
        };

        const loaded = await ensureIdentityKeyPair();

        expect(loaded.fingerprint).toBe(generated.fingerprint);
        expect(saveIdentity).toHaveBeenCalledTimes(1);
    });
});
