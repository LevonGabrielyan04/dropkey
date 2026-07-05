import { describe, expect, it } from 'vitest';

import { deserializeIdentity, serializeIdentity } from './identitySerialization.js';

describe('identitySerialization', () => {
    it('serializes and deserializes an identity key pair', async () => {
        const keyPair = await globalThis.crypto.subtle.generateKey(
            { name: 'ECDH', namedCurve: 'P-256' },
            true,
            ['deriveBits', 'deriveKey'],
        );
        const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

        const serialized = await serializeIdentity({
            privateKey: keyPair.privateKey,
            publicJwk,
        });

        const restored = await deserializeIdentity(serialized);

        expect(restored.publicJwk).toEqual(publicJwk);

        const [originalBits, restoredBits] = await Promise.all([
            globalThis.crypto.subtle.deriveBits(
                { name: 'ECDH', public: keyPair.publicKey },
                keyPair.privateKey,
                256,
            ),
            globalThis.crypto.subtle.deriveBits(
                { name: 'ECDH', public: keyPair.publicKey },
                restored.privateKey,
                256,
            ),
        ]);

        expect(Array.from(new Uint8Array(restoredBits))).toEqual(Array.from(new Uint8Array(originalBits)));
    });
});
