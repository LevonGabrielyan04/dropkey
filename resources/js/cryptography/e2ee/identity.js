import { fingerprintFromPublicJwk } from './bufferUtils.js';
import { loadIdentity, saveIdentity } from './keyStore.js';

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey, fingerprint: string }>}
 */
export async function ensureIdentityKeyPair() {
    const existing = await loadIdentity();

    if (existing) {
        const fingerprint = await fingerprintFromPublicJwk(existing.publicJwk);

        return {
            privateKey: existing.privateKey,
            publicJwk: existing.publicJwk,
            fingerprint,
        };
    }

    const keyPair = await globalThis.crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        false,
        ['deriveBits', 'deriveKey'],
    );

    const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

    await saveIdentity({
        privateKey: keyPair.privateKey,
        publicJwk,
    });

    const fingerprint = await fingerprintFromPublicJwk(publicJwk);

    return {
        privateKey: keyPair.privateKey,
        publicJwk,
        fingerprint,
    };
}

/**
 * Register the public key with the server.
 *
 * @param {string} registerUrl
 * @param {string} csrfToken
 */
export async function registerPublicKey(registerUrl, csrfToken) {
    const { publicJwk, fingerprint } = await ensureIdentityKeyPair();

    const response = await fetch(registerUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            public_key_jwk: publicJwk,
            fingerprint,
        }),
    });

    if (! response.ok) {
        throw new Error('Failed to register public key.');
    }

    return { publicJwk, fingerprint };
}

/**
 * @param {JsonWebKey} jwk
 */
export async function importPublicKey(jwk) {
    return globalThis.crypto.subtle.importKey(
        'jwk',
        jwk,
        { name: 'ECDH', namedCurve: 'P-256' },
        true,
        [],
    );
}
