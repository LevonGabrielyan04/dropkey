import { deriveConversationKey } from './conversationKey.js';
import { ensureIdentityKeyPair, ensureServerIdentityKey, importPublicKey } from './identity.js';
import { decryptMessage, encryptMessage } from './messageCrypto.js';

/**
 * @param {object} options
 * @param {number} options.localUserId
 * @param {number} options.recipientId
 * @param {string} options.publicKeyUrl
 * @param {string} options.registerUrl
 * @param {string} [options.mineUrl]
 * @param {string} options.csrfToken
 */
export async function establishSession({
    localUserId,
    recipientId,
    publicKeyUrl,
    registerUrl,
    mineUrl,
    csrfToken,
}) {
    await ensureServerIdentityKey({ registerUrl, mineUrl, csrfToken });

    const { privateKey } = await ensureIdentityKeyPair();

    const response = await fetch(publicKeyUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        throw new Error('Recipient has not registered an encryption key yet.');
    }

    const { public_key_jwk: publicJwk, fingerprint } = await response.json();
    const remotePublicKey = await importPublicKey(publicJwk);
    const conversationKey = await deriveConversationKey(
        privateKey,
        remotePublicKey,
        localUserId,
        recipientId,
    );

    return { conversationKey, partnerFingerprint: fingerprint };
}

export { decryptMessage as decryptChatMessage, encryptMessage as encryptChatMessage };
