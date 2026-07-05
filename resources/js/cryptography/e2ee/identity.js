import { fingerprintFromPublicJwk } from './bufferUtils.js';
import { loadIdentity, saveIdentity } from './identitySession.js';

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
        true,
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
 * Ensure the server has a registered public key for the current user.
 * Creates a local key pair and registers it only when the server has none.
 *
 * @param {object} options
 * @param {string} options.registerUrl
 * @param {string} options.mineUrl
 * @param {string} options.csrfToken
 */
export async function ensureServerIdentityKey({ registerUrl, mineUrl, csrfToken }) {
    const mineResponse = await fetch(mineUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (! mineResponse.ok) {
        throw new Error('Failed to check identity key registration status.');
    }

    const mine = await mineResponse.json();

    if (mine.registered) {
        await ensureIdentityKeyPair();

        return { registered: true };
    }

    await registerPublicKey(registerUrl, csrfToken);

    return { registered: false };
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
