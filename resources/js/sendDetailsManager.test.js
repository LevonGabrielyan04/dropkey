import { describe, expect, it } from 'vitest';
import { parseEncryptedPayload } from './cryptography/core/parseEncryptedPayload.js';

describe('parseEncryptedPayload', () => {
    it('returns null for plain text messages', () => {
        expect(parseEncryptedPayload('Hello, world!')).toBeNull();
    });

    it('returns null for empty strings', () => {
        expect(parseEncryptedPayload('')).toBeNull();
    });

    it('returns null for invalid JSON', () => {
        expect(parseEncryptedPayload('{not-json')).toBeNull();
    });

    it('returns null when required encryption fields are missing', () => {
        expect(parseEncryptedPayload(JSON.stringify({ ciphertext: 'abc' }))).toBeNull();
    });

    it('returns the payload when all encryption fields are present', () => {
        const payload = {
            ciphertext: 'abc',
            salt: 'def',
            iv: 'ghi',
        };

        expect(parseEncryptedPayload(JSON.stringify(payload))).toEqual(payload);
    });
});
