const DB_VERSION = 1;
const STORE_NAME = 'identity';
const DB_PREFIX = 'passshare-';

/**
 * @param {string} browserDbId
 * @returns {string}
 */
export function databaseNameForBrowserDbId(browserDbId) {
    return `${DB_PREFIX}${browserDbId}`;
}

/**
 * @returns {Promise<IDBDatabase>}
 */
function openDatabase(name) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(name, DB_VERSION);

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
 * @param {string} browserDbId
 * @returns {Promise<{ ciphertext: string, salt: string, iv: string }|null>}
 */
export async function loadEncryptedIdentity(browserDbId) {
    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.get('identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to load encrypted identity.'));
        request.onsuccess = () => resolve(request.result ?? null);
    });
}

/**
 * @param {string} browserDbId
 * @param {{ ciphertext: string, salt: string, iv: string }} encryptedIdentity
 * @returns {Promise<void>}
 */
export async function saveEncryptedIdentity(browserDbId, encryptedIdentity) {
    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.put(encryptedIdentity, 'identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to save encrypted identity.'));
        request.onsuccess = () => resolve();
    });
}
