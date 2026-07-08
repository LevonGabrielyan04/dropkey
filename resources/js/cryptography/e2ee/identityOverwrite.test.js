import { beforeEach, describe, expect, it, vi } from 'vitest';

const confirmIdentityKeyOverwrite = vi.hoisted(() => vi.fn(async () => true));

vi.mock('./identityOverwriteConfirmation.js', () => ({
    confirmIdentityKeyOverwrite,
}));

vi.mock('./identitySession.js', () => ({
    getSessionPassword: vi.fn(() => 'secret-password'),
    resolveBrowserDbId: vi.fn(() => '01JABCDEF1234567890ABCDEFGH'),
    unlockIdentity: vi.fn(async () => null),
}));

vi.mock('./keyStore.js', () => ({
    loadEncryptedIdentity: vi.fn(async () => null),
    loadUnlockedIdentity: vi.fn(async () => null),
}));

import { unlockIdentity } from './identitySession.js';
import { loadEncryptedIdentity, loadUnlockedIdentity } from './keyStore.js';
import {
    ensureIdentityOverwriteAllowed,
    IdentityKeyOverwriteCancelledError,
    wouldOverwriteLocalIdentity,
    wouldOverwriteServerIdentity,
} from './identityOverwrite.js';

describe('identityOverwrite', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(loadEncryptedIdentity).mockResolvedValue(null);
        vi.mocked(loadUnlockedIdentity).mockResolvedValue(null);
        vi.mocked(unlockIdentity).mockResolvedValue(null);
        confirmIdentityKeyOverwrite.mockResolvedValue(true);
    });

    it('does not detect local overwrite when no encrypted identity exists', async () => {
        await expect(wouldOverwriteLocalIdentity('01JABCDEF1234567890ABCDEFGH')).resolves.toBe(false);
    });

    it('does not detect local overwrite when an unlocked CryptoKey is already stored', async () => {
        vi.mocked(loadUnlockedIdentity).mockResolvedValue({
            privateKey: {},
            publicJwk: { kty: 'EC' },
        });
        vi.mocked(loadEncryptedIdentity).mockResolvedValue({
            v: 2,
            publicJwk: { kty: 'EC' },
        });

        await expect(wouldOverwriteLocalIdentity('01JABCDEF1234567890ABCDEFGH')).resolves.toBe(false);
        expect(unlockIdentity).not.toHaveBeenCalled();
    });

    it('detects local overwrite when encrypted identity cannot be unlocked', async () => {
        vi.mocked(loadEncryptedIdentity).mockResolvedValue({
            ciphertext: 'cipher',
            salt: 'salt',
            iv: 'iv',
        });

        await expect(wouldOverwriteLocalIdentity('01JABCDEF1234567890ABCDEFGH')).resolves.toBe(true);
    });

    it('does not detect local overwrite when encrypted identity unlocks', async () => {
        vi.mocked(loadEncryptedIdentity).mockResolvedValue({
            ciphertext: 'cipher',
            salt: 'salt',
            iv: 'iv',
        });
        vi.mocked(unlockIdentity).mockResolvedValue({
            privateKey: {},
            publicJwk: { kty: 'EC' },
        });

        await expect(wouldOverwriteLocalIdentity('01JABCDEF1234567890ABCDEFGH')).resolves.toBe(false);
    });

    it('detects server overwrite when fingerprints differ', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => ({
            ok: true,
            json: async () => ({
                registered: true,
                fingerprint: 'existing-fingerprint',
            }),
        })));

        await expect(wouldOverwriteServerIdentity('/api/identity/public-key/mine', 'new-fingerprint'))
            .resolves.toBe(true);

        vi.unstubAllGlobals();
    });

    it('does not detect server overwrite when fingerprints match', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => ({
            ok: true,
            json: async () => ({
                registered: true,
                fingerprint: 'same-fingerprint',
            }),
        })));

        await expect(wouldOverwriteServerIdentity('/api/identity/public-key/mine', 'same-fingerprint'))
            .resolves.toBe(false);

        vi.unstubAllGlobals();
    });

    it('prompts for confirmation before allowing overwrite', async () => {
        vi.mocked(loadEncryptedIdentity).mockResolvedValue({
            ciphertext: 'cipher',
            salt: 'salt',
            iv: 'iv',
        });

        await ensureIdentityOverwriteAllowed({
            checkLocal: true,
            checkServer: false,
            browserDbId: '01JABCDEF1234567890ABCDEFGH',
        });

        expect(confirmIdentityKeyOverwrite).toHaveBeenCalledOnce();
    });

    it('throws when overwrite confirmation is declined', async () => {
        confirmIdentityKeyOverwrite.mockResolvedValue(false);
        vi.mocked(loadEncryptedIdentity).mockResolvedValue({
            ciphertext: 'cipher',
            salt: 'salt',
            iv: 'iv',
        });

        await expect(ensureIdentityOverwriteAllowed({
            checkLocal: true,
            checkServer: false,
            browserDbId: '01JABCDEF1234567890ABCDEFGH',
        })).rejects.toBeInstanceOf(IdentityKeyOverwriteCancelledError);
    });
});
