import { describe, expect, it, vi } from 'vitest';

vi.mock('hash-wasm', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        argon2id: vi.fn((...args) => actual.argon2id(...args)),
    };
});

import { argon2id } from 'hash-wasm';
import {
    createIdentityEnvelope,
    decryptData,
    deriveKey,
    deriveKek,
    encryptData,
    generateDek,
    unlockIdentityEnvelope,
    unwrapDek,
    wrapDek,
} from './cryptoUtils.js';

const password = 'test-password-123';
const testSalt = new Uint8Array([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]);

async function expectRoundTrip(plaintext) {
    const encrypted = await encryptData(plaintext, password);
    const decrypted = await decryptData(encrypted, password);

    expect(decrypted).toBe(plaintext);
}

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
async function generateIdentity() {
    const keyPair = await globalThis.crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        true,
        ['deriveBits', 'deriveKey'],
    );
    const publicJwk = await globalThis.crypto.subtle.exportKey('jwk', keyPair.publicKey);

    return {
        privateKey: keyPair.privateKey,
        publicJwk,
    };
}

describe('encryptData / decryptData', () => {
    it('encrypts and decrypts small text (15-30 characters)', async () => {
        await expectRoundTrip('Hello, secure world!');
    });

    it('encrypts and decrypts 1000 character text', async () => {
        const plaintext = 'a'.repeat(1000);

        await expectRoundTrip(plaintext);
    });

    it('encrypts and decrypts single character text', async () => {
        await expectRoundTrip('x');
    });
});

describe('deriveKey', () => {
    it('generates a secure, non-extractable AES-GCM key', async () => {
        const key = await deriveKey('my-secure-password', testSalt);

        expect(key.type).toBe('secret');
        expect(key.algorithm.name).toBe('AES-GCM');
        expect(key.algorithm.length).toBe(256);
        expect(key.extractable).toBe(false);
        expect(key.usages).toHaveLength(2);
        expect(key.usages).toEqual(expect.arrayContaining(['encrypt', 'decrypt']));
    });

    it('produces deterministic, functional keys', async () => {
        const salt = globalThis.crypto.getRandomValues(new Uint8Array(16));

        const key1 = await deriveKey(password, salt);
        const key2 = await deriveKey(password, salt);

        const encoder = new TextEncoder();
        const decoder = new TextDecoder();
        const secretMessage = 'The eagle flies at midnight';
        const iv = globalThis.crypto.getRandomValues(new Uint8Array(12));

        const cipherText = await globalThis.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            key1,
            encoder.encode(secretMessage),
        );

        const decryptedBuffer = await globalThis.crypto.subtle.decrypt(
            { name: 'AES-GCM', iv },
            key2,
            cipherText,
        );

        expect(decoder.decode(decryptedBuffer)).toBe(secretMessage);
    });
});

describe('deriveKey (mocked argon2)', () => {
    it('imports argon2 output as a functional AES-GCM key', async () => {
        argon2id.mockResolvedValueOnce(new Uint8Array(32).fill(1));

        const key = await deriveKey(password, testSalt);

        expect(key.type).toBe('secret');
        expect(key.algorithm.name).toBe('AES-GCM');
        expect(key.algorithm.length).toBe(256);
        expect(key.extractable).toBe(false);
        expect(key.usages).toHaveLength(2);
        expect(key.usages).toEqual(expect.arrayContaining(['encrypt', 'decrypt']));
    });
});

describe('KEK / DEK helpers', () => {
    it('derives a non-extractable KEK with wrapKey usages', async () => {
        const kek = await deriveKek(password, testSalt);

        expect(kek.extractable).toBe(false);
        expect(kek.usages).toHaveLength(2);
        expect(kek.usages).toEqual(expect.arrayContaining(['wrapKey', 'unwrapKey']));
    });

    it('wraps and unwraps a DEK with a KEK', async () => {
        const kek = await deriveKek(password, testSalt);
        const dek = await generateDek();

        expect(dek.extractable).toBe(true);

        const wrapped = await wrapDek(dek, kek);
        const unwrapped = await unwrapDek(wrapped, kek);

        expect(unwrapped.extractable).toBe(false);
        expect(unwrapped.usages).toHaveLength(2);
        expect(unwrapped.usages).toEqual(expect.arrayContaining(['wrapKey', 'unwrapKey']));
    });

    it('creates and unlocks an identity envelope', async () => {
        const identity = await generateIdentity();

        const { envelope, unlockedPrivateKey } = await createIdentityEnvelope(password, identity);

        expect(envelope.v).toBe(2);
        expect(envelope.publicJwk).toEqual(identity.publicJwk);
        expect(unlockedPrivateKey.extractable).toBe(false);

        const unlocked = await unlockIdentityEnvelope(password, envelope);

        expect(unlocked.publicJwk).toEqual(identity.publicJwk);
        expect(unlocked.privateKey.extractable).toBe(false);
        expect(unlocked.privateKey.usages).toHaveLength(2);
        expect(unlocked.privateKey.usages).toEqual(
            expect.arrayContaining(['deriveBits', 'deriveKey']),
        );
    });

    it('rejects non-extractable keys when creating an envelope', async () => {
        const identity = await generateIdentity();
        const nonExtractable = await globalThis.crypto.subtle.importKey(
            'jwk',
            await globalThis.crypto.subtle.exportKey('jwk', identity.privateKey),
            { name: 'ECDH', namedCurve: 'P-256' },
            false,
            ['deriveBits', 'deriveKey'],
        );

        await expect(createIdentityEnvelope(password, {
            privateKey: nonExtractable,
            publicJwk: identity.publicJwk,
        })).rejects.toThrow('Identity private key must be extractable');
    });
});
