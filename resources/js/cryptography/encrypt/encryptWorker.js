import { encryptData } from '../core/cryptoUtils.js';

self.addEventListener('message', async (event) => {
    const { plaintext, password } = event.data;

    try {
        const result = await encryptData(plaintext, password);
        self.postMessage({ ok: true, data: result });
    } catch (error) {
        self.postMessage({
            ok: false,
            error: error instanceof Error ? error.message : 'Encryption failed.',
        });
    }
});
