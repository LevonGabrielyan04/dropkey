import { decryptData } from '../core/cryptoUtils.js';

self.addEventListener('message', async (event) => {
    const { encryptedObj, password } = event.data;

    try {
        const result = await decryptData(encryptedObj, password);
        self.postMessage({ ok: true, data: result });
    } catch (error) {
        self.postMessage({
            ok: false,
            error: error instanceof Error ? error.message : 'Decryption failed.',
        });
    }
});
