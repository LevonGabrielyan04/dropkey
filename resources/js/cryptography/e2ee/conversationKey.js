/**
 * Derive a deterministic AES-GCM conversation key for a user pair.
 *
 * @param {CryptoKey} localPrivateKey
 * @param {CryptoKey} remotePublicKey
 * @param {number} localUserId
 * @param {number} remoteUserId
 */
export async function deriveConversationKey(
    localPrivateKey,
    remotePublicKey,
    localUserId,
    remoteUserId,
) {
    const sharedBits = await globalThis.crypto.subtle.deriveBits(
        { name: 'ECDH', public: remotePublicKey },
        localPrivateKey,
        256,
    );

    const [userOneId, userTwoId] = localUserId < remoteUserId
        ? [localUserId, remoteUserId]
        : [remoteUserId, localUserId];

    const info = new TextEncoder().encode(`passshare:conv:${userOneId}:${userTwoId}`);

    const hkdfKey = await globalThis.crypto.subtle.importKey(
        'raw',
        sharedBits,
        'HKDF',
        false,
        ['deriveKey'],
    );

    return globalThis.crypto.subtle.deriveKey(
        {
            name: 'HKDF',
            hash: 'SHA-256',
            salt: new Uint8Array(0),
            info,
        },
        hkdfKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );
}

/**
 * @param {number} localUserId
 * @param {number} remoteUserId
 */
export function conversationInfo(localUserId, remoteUserId) {
    const [userOneId, userTwoId] = localUserId < remoteUserId
        ? [localUserId, remoteUserId]
        : [remoteUserId, localUserId];

    return `passshare:conv:${userOneId}:${userTwoId}`;
}
