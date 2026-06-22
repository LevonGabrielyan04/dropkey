import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { decryptData, encryptData } from '../core/cryptoUtils.js';

const password = 'test-password-123';

class MockDecryptWorker {
    onmessage = null;

    onerror = null;

    postMessage(data) {
        queueMicrotask(async () => {
            try {
                const result = await decryptData(data.encryptedObj, data.password);
                this.onmessage?.({ data: { ok: true, data: result } });
            } catch (error) {
                this.onmessage?.({
                    data: {
                        ok: false,
                        error: error instanceof Error ? error.message : 'Decryption failed.',
                    },
                });
            }
        });
    }

    terminate() {}
}

vi.mock('./decryptWorker.js?worker&inline', () => ({
    default: MockDecryptWorker,
}));

async function expectWorkerRoundTrip(plaintext) {
    const { decryptViaWorker } = await import('./decryptViaWorker.js');
    const encrypted = await encryptData(plaintext, password);
    const decrypted = await decryptViaWorker(encrypted, password);

    expect(decrypted).toBe(plaintext);
}

describe('decryptViaWorker', () => {
    beforeEach(() => {
        vi.stubGlobal('Worker', MockDecryptWorker);
    });

    afterEach(() => {
        vi.resetModules();
        vi.unstubAllGlobals();
    });

    it('decrypts small text off the main thread', async () => {
        await expectWorkerRoundTrip('Hello, secure world!');
    });

    it('decrypts 1000 character text off the main thread', async () => {
        await expectWorkerRoundTrip('a'.repeat(1000));
    });

    it('rejects invalid ciphertext', async () => {
        const { decryptViaWorker } = await import('./decryptViaWorker.js');
        const encrypted = await encryptData('secret', password);

        await expect(
            decryptViaWorker({ ...encrypted, ciphertext: 'invalid' }, password),
        ).rejects.toThrow();
    });
});
