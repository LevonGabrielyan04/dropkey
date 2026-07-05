import { beforeEach, describe, expect, it, vi } from 'vitest';

const ensureServerIdentityKey = vi.hoisted(() => vi.fn(async () => ({ registered: false })));
const unlockIdentity = vi.hoisted(() => vi.fn(async () => null));
const getSessionPassword = vi.hoisted(() => vi.fn(() => 'secret-password'));
const setSessionUsername = vi.hoisted(() => vi.fn());

vi.mock('./cryptography/e2ee/identity.js', () => ({
    ensureServerIdentityKey,
}));

vi.mock('./cryptography/e2ee/identitySession.js', () => ({
    getSessionPassword,
    setSessionUsername,
    unlockIdentity,
}));

describe('identityRegistration bootstrap', () => {
    beforeEach(() => {
        ensureServerIdentityKey.mockReset();
        unlockIdentity.mockReset();
        getSessionPassword.mockReset();
        getSessionPassword.mockReturnValue('secret-password');
        setSessionUsername.mockReset();
    });

    it('unlocks identity and registers a key when bootstrap data attributes are present', async () => {
        const doc = {
            body: {
                dataset: {
                    username: 'alice',
                    identityRegisterUrl: '/api/identity/public-key',
                    identityMineUrl: '/api/identity/public-key/mine',
                    csrfToken: 'csrf-token',
                },
            },
        };

        const { bootstrapIdentityRegistration } = await import('./identityRegistration.js');

        await bootstrapIdentityRegistration(doc);

        expect(setSessionUsername).toHaveBeenCalledWith('alice');
        expect(unlockIdentity).toHaveBeenCalledWith('alice', 'secret-password');
        expect(ensureServerIdentityKey).toHaveBeenCalledWith({
            registerUrl: '/api/identity/public-key',
            mineUrl: '/api/identity/public-key/mine',
            csrfToken: 'csrf-token',
        });
    });

    it('skips unlock when no session password is available', async () => {
        getSessionPassword.mockReturnValue(null);

        const doc = {
            body: {
                dataset: {
                    username: 'alice',
                    identityRegisterUrl: '/api/identity/public-key',
                    identityMineUrl: '/api/identity/public-key/mine',
                    csrfToken: 'csrf-token',
                },
            },
        };

        const { bootstrapIdentityRegistration } = await import('./identityRegistration.js');

        await bootstrapIdentityRegistration(doc);

        expect(unlockIdentity).not.toHaveBeenCalled();
        expect(ensureServerIdentityKey).toHaveBeenCalledOnce();
    });

    it('skips registration when bootstrap data attributes are missing', async () => {
        const doc = {
            body: {
                dataset: {},
            },
        };

        const { bootstrapIdentityRegistration } = await import('./identityRegistration.js');

        await bootstrapIdentityRegistration(doc);

        expect(ensureServerIdentityKey).not.toHaveBeenCalled();
    });
});
