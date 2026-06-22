import { argon2id } from "hash-wasm";
import {
    AES_KEY_LENGTH_BYTES,
    ARGON2_ITERATIONS,
    ARGON2_MEMORY_SIZE_KIB,
    ARGON2_PARALLELISM,
} from "./config.js";

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

export async function deriveKey(password, salt) {
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
        { name: "AES-GCM", length: 256 },
        false,
        ["encrypt", "decrypt"]
    );
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
