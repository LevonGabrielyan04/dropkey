const DB_VERSION = 1;
const STORE_NAME = 'identity';
const DB_PREFIX = 'passshare-';

/**
 * @param {string} username
 * @returns {string}
 */
export function databaseNameForUser(username) {
    return `${DB_PREFIX}${username}`;
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
 * @param {string} username
 * @returns {Promise<{ ciphertext: string, salt: string, iv: string }|null>}
 */
export async function loadEncryptedIdentity(username) {
    const database = await openDatabase(databaseNameForUser(username));

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.get('identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to load encrypted identity.'));
        request.onsuccess = () => resolve(request.result ?? null);
    });
}

/**
 * @param {string} username
 * @param {{ ciphertext: string, salt: string, iv: string }} encryptedIdentity
 * @returns {Promise<void>}
 */
export async function saveEncryptedIdentity(username, encryptedIdentity) {
    const database = await openDatabase(databaseNameForUser(username));

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.put(encryptedIdentity, 'identity');

        request.onerror = () => reject(request.error ?? new Error('Failed to save encrypted identity.'));
        request.onsuccess = () => resolve();
    });
}
