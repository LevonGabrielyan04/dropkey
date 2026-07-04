import { describe, expect, it } from 'vitest';
import { fingerprintFromPublicJwk, sha256Fingerprint } from './bufferUtils.js';

describe('bufferUtils', () => {
    it('computes a stable public key fingerprint', async () => {
        const jwk = {
            kty: 'EC',
            crv: 'P-256',
            x: 'abc',
            y: 'def',
        };

        const first = await fingerprintFromPublicJwk(jwk);
        const second = await fingerprintFromPublicJwk(jwk);

        expect(first).toBe(second);
        expect(first).toMatch(/^[a-f0-9]{64}$/);
    });

    it('changes the fingerprint when the public key changes', async () => {
        const base = {
            kty: 'EC',
            crv: 'P-256',
            x: 'abc',
            y: 'def',
        };

        const original = await fingerprintFromPublicJwk(base);
        const rotated = await fingerprintFromPublicJwk({ ...base, x: 'xyz' });

        expect(original).not.toBe(rotated);
    });

    it('hashes binary input with sha256', async () => {
        const digest = await sha256Fingerprint(new TextEncoder().encode('passshare'));

        expect(digest).toMatch(/^[a-f0-9]{64}$/);
    });
});
