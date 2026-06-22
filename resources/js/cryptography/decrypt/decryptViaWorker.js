// We still import this standard worker for the production build
import DecryptWorker from './decryptWorker.js?worker&inline';

/**
 * Decrypt ciphertext off the main thread via decryptWorker.js.
 *
 * @param {{ ciphertext: string, salt: string, iv: string }} encryptedObj
 * @param {string} password
 * @returns {Promise<string>}
 */
export function decryptViaWorker(encryptedObj, password) {
    return new Promise((resolve, reject) => {
        let worker;

        if (import.meta.env.DEV) {
            // Dev Mode Workaround: Create a local Blob that imports the remote Vite module
            const workerUrl = new URL('./decryptWorker.js', import.meta.url).href;
            const blob = new Blob([`import "${workerUrl}";`], { type: 'application/javascript' });
            worker = new Worker(URL.createObjectURL(blob), { type: 'module' });
        } else {
            // Production Mode: Vite's ?worker&inline handles this perfectly
            worker = new DecryptWorker();
        }

        worker.onmessage = (event) => {
            worker.terminate();

            if (event.data.ok) {
                resolve(event.data.data);
                return;
            }

            reject(new Error(event.data.error ?? 'Decryption failed.'));
        };

        worker.onerror = (error) => {
            worker.terminate();
            reject(error);
        };

        worker.postMessage({ encryptedObj, password });
    });
}
