const ECDH_ALGORITHM = { name: 'ECDH', namedCurve: 'P-256' };

/**
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<string>}
 */
export async function serializeIdentity(identity) {
    const privateJwk = await globalThis.crypto.subtle.exportKey('jwk', identity.privateKey);

    return JSON.stringify({
        publicJwk: identity.publicJwk,
        privateJwk,
    });
}

/**
 * @param {string} serialized
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
export async function deserializeIdentity(serialized) {
    const { publicJwk, privateJwk } = JSON.parse(serialized);

    const privateKey = await globalThis.crypto.subtle.importKey(
        'jwk',
        privateJwk,
        ECDH_ALGORITHM,
        false,
        ['deriveBits', 'deriveKey'],
    );

    return {
        privateKey,
        publicJwk,
    };
}
