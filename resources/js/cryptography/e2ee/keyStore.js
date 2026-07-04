const DB_NAME = 'passshare-e2ee';
const DB_VERSION = 1;
const STORE_NAME = 'identity';

/**
 * @returns {Promise<IDBDatabase>}
 */
function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error ?? new Error('Failed to open IndexedDB.'));
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = () => {
            const database = request.result;

            if (! database.objectStoreNames.contains(STORE_NAME)) {
                database.createObjectStore(STORE_NAME);
            }
        };
    });
}

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function loadIdentity() {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.get('identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to load identity.'));
        request.onsuccess = () => resolve(request.result ?? null);
    });
}

/**
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 */
export async function saveIdentity(identity) {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.put(identity, 'identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to save identity.'));
        request.onsuccess = () => resolve();
    });
}

/**
 * @returns {Promise<void>}
 */
export async function clearIdentity() {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.delete('identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to clear identity.'));
        request.onsuccess = () => resolve();
    });
}
