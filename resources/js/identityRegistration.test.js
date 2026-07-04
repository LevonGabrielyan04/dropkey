import { beforeEach, describe, expect, it, vi } from 'vitest';

const ensureServerIdentityKey = vi.hoisted(() => vi.fn(async () => ({ registered: false })));

vi.mock('./cryptography/e2ee/identity.js', () => ({
    ensureServerIdentityKey,
}));

describe('identityRegistration bootstrap', () => {
    beforeEach(() => {
        ensureServerIdentityKey.mockReset();
    });

    it('registers an identity key when bootstrap data attributes are present', async () => {
        const doc = {
            body: {
                dataset: {
                    identityRegisterUrl: '/api/identity/public-key',
                    identityMineUrl: '/api/identity/public-key/mine',
                    csrfToken: 'csrf-token',
                },
            },
        };

        const { bootstrapIdentityRegistration } = await import('./identityRegistration.js');

        bootstrapIdentityRegistration(doc);

        expect(ensureServerIdentityKey).toHaveBeenCalledWith({
            registerUrl: '/api/identity/public-key',
            mineUrl: '/api/identity/public-key/mine',
            csrfToken: 'csrf-token',
        });
    });

    it('skips registration when bootstrap data attributes are missing', async () => {
        const doc = {
            body: {
                dataset: {},
            },
        };

        const { bootstrapIdentityRegistration } = await import('./identityRegistration.js');

        bootstrapIdentityRegistration(doc);

        expect(ensureServerIdentityKey).not.toHaveBeenCalled();
    });
});
