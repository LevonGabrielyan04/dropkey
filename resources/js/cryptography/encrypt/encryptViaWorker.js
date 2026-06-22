// We still import this standard worker for the production build
import EncryptWorker from './encryptWorker.js?worker&inline';

/**
 * Encrypt plaintext off the main thread via encryptWorker.js.
 *
 * @param {string} plaintext
 * @param {string} password
 * @returns {Promise<{ ciphertext: string, salt: string, iv: string }>}
 */
export function encryptViaWorker(plaintext, password) {
    return new Promise((resolve, reject) => {
        let worker;

        if (import.meta.env.DEV) {
            // Dev Mode Workaround: Create a local Blob that imports the remote Vite module
            const workerUrl = new URL('./encryptWorker.js', import.meta.url).href;
            const blob = new Blob([`import "${workerUrl}";`], { type: 'application/javascript' });
            worker = new Worker(URL.createObjectURL(blob), { type: 'module' });
        } else {
            // Production Mode: Vite's ?worker&inline handles this perfectly
            worker = new EncryptWorker();
        }

        worker.onmessage = (event) => {
            worker.terminate();

            if (event.data.ok) {
                resolve(event.data.data);
                return;
            }

            reject(new Error(event.data.error ?? 'Encryption failed.'));
        };

        worker.onerror = (error) => {
            worker.terminate();
            reject(error);
        };

        worker.postMessage({ plaintext, password });
    });
}
