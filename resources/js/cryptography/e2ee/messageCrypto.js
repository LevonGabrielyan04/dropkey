import { base64ToBuffer, bufferToBase64 } from './bufferUtils.js';

export const PAYLOAD_VERSION = 1;

/**
 * @param {string} plaintext
 * @param {CryptoKey} conversationKey
 */
export async function encryptMessage(plaintext, conversationKey) {
    const iv = globalThis.crypto.getRandomValues(new Uint8Array(12));
    const ciphertext = await globalThis.crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        conversationKey,
        new TextEncoder().encode(plaintext),
    );

    return JSON.stringify({
        v: PAYLOAD_VERSION,
        ciphertext: bufferToBase64(ciphertext),
        iv: bufferToBase64(iv),
    });
}

/**
 * @param {string} payloadJson
 * @param {CryptoKey} conversationKey
 */
export async function decryptMessage(payloadJson, conversationKey) {
    const payload = JSON.parse(payloadJson);

    if (payload.v !== PAYLOAD_VERSION) {
        throw new Error('Unsupported message payload version.');
    }

    const iv = base64ToBuffer(payload.iv);
    const ciphertext = base64ToBuffer(payload.ciphertext);

    const plaintextBuffer = await globalThis.crypto.subtle.decrypt(
        { name: 'AES-GCM', iv },
        conversationKey,
        ciphertext,
    );

    return new TextDecoder().decode(plaintextBuffer);
}

/**
 * @param {string} payloadJson
 */
export function parseChatPayload(payloadJson) {
    let payload;

    try {
        payload = JSON.parse(payloadJson);
    } catch {
        return null;
    }

    if (
        payload?.v !== PAYLOAD_VERSION
        || typeof payload.ciphertext !== 'string'
        || typeof payload.iv !== 'string'
    ) {
        return null;
    }

    return payload;
}
