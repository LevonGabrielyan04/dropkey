import { argon2id } from "hash-wasm";
import {
    AES_KEY_LENGTH_BYTES,
    ARGON2_ITERATIONS,
    ARGON2_MEMORY_SIZE_KIB,
    ARGON2_PARALLELISM,
} from "./config.js";

export const AES_GCM_ALGORITHM = { name: "AES-GCM", length: 256 };
export const ECDH_P256_ALGORITHM = { name: "ECDH", namedCurve: "P-256" };
export const IDENTITY_KEY_USAGES = /** @type {KeyUsage[]} */ (["deriveBits", "deriveKey"]);
export const KEK_KEY_USAGES = /** @type {KeyUsage[]} */ (["wrapKey", "unwrapKey"]);
export const DEK_KEY_USAGES = /** @type {KeyUsage[]} */ (["wrapKey", "unwrapKey"]);

function generateRandomBytes(length) {
    return globalThis.crypto.getRandomValues(new Uint8Array(length));
}

function bufferToBase64(buffer) {
    const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}

function base64ToBuffer(base64) {
    const binaryString = atob(base64);
    const bytes = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes;
}

/**
 * @param {string} password
 * @param {BufferSource} salt
 * @param {KeyUsage[]} [usages]
 * @returns {Promise<CryptoKey>}
 */
export async function deriveKey(password, salt, usages = ["encrypt", "decrypt"]) {
    const encoder = new TextEncoder();
    const derivedKeyBytes = await argon2id({
        password: encoder.encode(password),
        salt,
        memorySize: ARGON2_MEMORY_SIZE_KIB,
        iterations: ARGON2_ITERATIONS,
        parallelism: ARGON2_PARALLELISM,
        hashLength: AES_KEY_LENGTH_BYTES,
        outputType: "binary",
    });

    return globalThis.crypto.subtle.importKey(
        "raw",
        derivedKeyBytes,
        AES_GCM_ALGORITHM,
        false,
        usages,
    );
}

/**
 * Derive a non-extractable KEK suitable for wrapKey / unwrapKey.
 *
 * @param {string} password
 * @param {BufferSource} salt
 * @returns {Promise<CryptoKey>}
 */
export async function deriveKek(password, salt) {
    return deriveKey(password, salt, KEK_KEY_USAGES);
}

/**
 * Generate an extractable AES-GCM DEK that can be wrapped by a KEK.
 *
 * @returns {Promise<CryptoKey>}
 */
export async function generateDek() {
    return globalThis.crypto.subtle.generateKey(
        AES_GCM_ALGORITHM,
        true,
        DEK_KEY_USAGES,
    );
}

/**
 * @param {CryptoKey} key
 * @param {CryptoKey} wrappingKey
 * @param {"raw"|"jwk"} [format]
 * @returns {Promise<{ ciphertext: string, iv: string }>}
 */
export async function wrapCryptoKey(key, wrappingKey, format = "jwk") {
    const iv = generateRandomBytes(12);
    const wrapped = await globalThis.crypto.subtle.wrapKey(
        format,
        key,
        wrappingKey,
        { name: "AES-GCM", iv },
    );

    return {
        ciphertext: bufferToBase64(wrapped),
        iv: bufferToBase64(iv),
    };
}

/**
 * @param {{ ciphertext: string, iv: string }} wrapped
 * @param {CryptoKey} wrappingKey
 * @param {AlgorithmIdentifier|RsaHashedImportParams|EcKeyImportParams|HmacImportParams|AesKeyAlgorithm} algorithm
 * @param {KeyUsage[]} usages
 * @param {boolean} [extractable]
 * @param {"raw"|"jwk"} [format]
 * @returns {Promise<CryptoKey>}
 */
export async function unwrapCryptoKey(
    wrapped,
    wrappingKey,
    algorithm,
    usages,
    extractable = false,
    format = "jwk",
) {
    const iv = base64ToBuffer(wrapped.iv);
    const ciphertext = base64ToBuffer(wrapped.ciphertext);

    return globalThis.crypto.subtle.unwrapKey(
        format,
        ciphertext,
        wrappingKey,
        { name: "AES-GCM", iv },
        algorithm,
        extractable,
        usages,
    );
}

/**
 * @param {CryptoKey} dek
 * @param {CryptoKey} kek
 * @returns {Promise<{ ciphertext: string, iv: string }>}
 */
export async function wrapDek(dek, kek) {
    return wrapCryptoKey(dek, kek, "raw");
}

/**
 * @param {{ ciphertext: string, iv: string }} wrappedDek
 * @param {CryptoKey} kek
 * @param {boolean} [extractable]
 * @returns {Promise<CryptoKey>}
 */
export async function unwrapDek(wrappedDek, kek, extractable = false) {
    return unwrapCryptoKey(
        wrappedDek,
        kek,
        AES_GCM_ALGORITHM,
        DEK_KEY_USAGES,
        extractable,
        "raw",
    );
}

/**
 * @param {CryptoKey} identityPrivateKey
 * @param {CryptoKey} dek
 * @returns {Promise<{ ciphertext: string, iv: string }>}
 */
export async function wrapIdentityKey(identityPrivateKey, dek) {
    return wrapCryptoKey(identityPrivateKey, dek, "jwk");
}

/**
 * @param {{ ciphertext: string, iv: string }} wrappedIdentity
 * @param {CryptoKey} dek
 * @returns {Promise<CryptoKey>}
 */
export async function unwrapIdentityKey(wrappedIdentity, dek) {
    return unwrapCryptoKey(
        wrappedIdentity,
        dek,
        ECDH_P256_ALGORITHM,
        IDENTITY_KEY_USAGES,
        false,
        "jwk",
    );
}

/**
 * Ensure an ECDH private key is non-extractable (safe for IndexedDB structured clone).
 *
 * @param {CryptoKey} privateKey
 * @returns {Promise<CryptoKey>}
 */
export async function toNonExtractableIdentityKey(privateKey) {
    if (! privateKey.extractable) {
        return privateKey;
    }

    const jwk = await globalThis.crypto.subtle.exportKey("jwk", privateKey);

    return globalThis.crypto.subtle.importKey(
        "jwk",
        jwk,
        ECDH_P256_ALGORITHM,
        false,
        IDENTITY_KEY_USAGES,
    );
}

/**
 * Build a password-protected KEK/DEK identity envelope and a non-extractable unlocked key.
 *
 * @param {string} password
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<{
 *   envelope: {
 *     v: 2,
 *     publicJwk: JsonWebKey,
 *     kekSalt: string,
 *     wrappedDek: { ciphertext: string, iv: string },
 *     wrappedIdentity: { ciphertext: string, iv: string },
 *   },
 *   unlockedPrivateKey: CryptoKey,
 * }>}
 */
export async function createIdentityEnvelope(password, identity) {
    if (! identity.privateKey.extractable) {
        throw new Error('Identity private key must be extractable to create a KEK/DEK envelope.');
    }

    const kekSalt = generateRandomBytes(16);
    const kek = await deriveKek(password, kekSalt);
    const dek = await generateDek();

    const wrappedDek = await wrapDek(dek, kek);
    const wrappedIdentity = await wrapIdentityKey(identity.privateKey, dek);
    const unlockedPrivateKey = await toNonExtractableIdentityKey(identity.privateKey);

    return {
        envelope: {
            v: 2,
            publicJwk: identity.publicJwk,
            kekSalt: bufferToBase64(kekSalt),
            wrappedDek,
            wrappedIdentity,
        },
        unlockedPrivateKey,
    };
}

/**
 * Unlock a v2 identity envelope with the account password.
 *
 * @param {string} password
 * @param {{
 *   publicJwk: JsonWebKey,
 *   kekSalt: string,
 *   wrappedDek: { ciphertext: string, iv: string },
 *   wrappedIdentity: { ciphertext: string, iv: string },
 * }} envelope
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
export async function unlockIdentityEnvelope(password, envelope) {
    const kek = await deriveKek(password, base64ToBuffer(envelope.kekSalt));
    const dek = await unwrapDek(envelope.wrappedDek, kek);
    const privateKey = await unwrapIdentityKey(envelope.wrappedIdentity, dek);

    return {
        privateKey,
        publicJwk: envelope.publicJwk,
    };
}

export async function encryptData(plaintext, password) {
    const encoder = new TextEncoder();
    const salt = generateRandomBytes(16);
    const iv = generateRandomBytes(12);

    const key = await deriveKey(password, salt);

    const ciphertext = await globalThis.crypto.subtle.encrypt(
        { name: "AES-GCM", iv: iv },
        key,
        encoder.encode(plaintext)
    );

    return {
        ciphertext: bufferToBase64(ciphertext),
        salt: bufferToBase64(salt),
        iv: bufferToBase64(iv)
    };
}

export async function decryptData(encryptedObj, password) {
    const decoder = new TextDecoder();

    const saltBuffer = base64ToBuffer(encryptedObj.salt);
    const ivBuffer = base64ToBuffer(encryptedObj.iv);
    const ciphertextBuffer = base64ToBuffer(encryptedObj.ciphertext);

    const key = await deriveKey(password, saltBuffer);

    const decryptedBuffer = await globalThis.crypto.subtle.decrypt(
        { name: "AES-GCM", iv: ivBuffer },
        key,
        ciphertextBuffer
    );

    return decoder.decode(decryptedBuffer);
}

export { bufferToBase64, base64ToBuffer, generateRandomBytes };
