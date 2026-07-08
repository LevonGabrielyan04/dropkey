const DB_VERSION = 2;
const STORE_NAME = 'identity';
const DB_PREFIX = 'passshare-';

export const IDENTITY_RECORD_KEY = 'identity';
export const UNLOCKED_IDENTITY_KEY = 'unlockedIdentity';
export const IDENTITY_ENVELOPE_VERSION = 2;

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
 * @param {IDBDatabase} database
 * @param {IDBTransactionMode} mode
 * @returns {IDBObjectStore}
 */
function identityStore(database, mode) {
    return database.transaction(STORE_NAME, mode).objectStore(STORE_NAME);
}

/**
 * @param {unknown} value
 * @returns {value is {
 *   ciphertext: string,
 *   salt: string,
 *   iv: string,
 * }}
 */
export function isLegacyEncryptedIdentity(value) {
    return Boolean(
        value
        && typeof value === 'object'
        && ! ('v' in value)
        && typeof value.ciphertext === 'string'
        && typeof value.salt === 'string'
        && typeof value.iv === 'string',
    );
}

/**
 * @param {unknown} value
 * @returns {value is {
 *   v: 2,
 *   publicJwk: JsonWebKey,
 *   kekSalt: string,
 *   wrappedDek: { ciphertext: string, iv: string },
 *   wrappedIdentity: { ciphertext: string, iv: string },
 * }}
 */
export function isIdentityEnvelope(value) {
    return Boolean(
        value
        && typeof value === 'object'
        && value.v === IDENTITY_ENVELOPE_VERSION
        && value.publicJwk
        && typeof value.kekSalt === 'string'
        && value.wrappedDek
        && typeof value.wrappedDek.ciphertext === 'string'
        && typeof value.wrappedDek.iv === 'string'
        && value.wrappedIdentity
        && typeof value.wrappedIdentity.ciphertext === 'string'
        && typeof value.wrappedIdentity.iv === 'string',
    );
}

/**
 * Load the password-protected identity record (legacy blob or v2 envelope).
 *
 * @param {string} browserDbId
 * @returns {Promise<object|null>}
 */
export async function loadEncryptedIdentity(browserDbId) {
    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const request = identityStore(database, 'readonly').get(IDENTITY_RECORD_KEY);

        request.onerror = () => reject(request.error ?? new Error('Failed to load encrypted identity.'));
        request.onsuccess = () => resolve(request.result ?? null);
    });
}

/**
 * Persist a password-protected identity envelope (or legacy blob during tests).
 *
 * @param {string} browserDbId
 * @param {object} encryptedIdentity
 * @returns {Promise<void>}
 */
export async function saveEncryptedIdentity(browserDbId, encryptedIdentity) {
    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const request = identityStore(database, 'readwrite').put(encryptedIdentity, IDENTITY_RECORD_KEY);

        request.onerror = () => reject(request.error ?? new Error('Failed to save encrypted identity.'));
        request.onsuccess = () => resolve();
    });
}

/**
 * @typedef {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} UnlockedIdentityRecord
 */

/**
 * Load a non-extractable identity CryptoKey previously stored via structured clone.
 *
 * @param {string} browserDbId
 * @returns {Promise<UnlockedIdentityRecord|null>}
 */
export async function loadUnlockedIdentity(browserDbId) {
    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const request = identityStore(database, 'readonly').get(UNLOCKED_IDENTITY_KEY);

        request.onerror = () => reject(request.error ?? new Error('Failed to load unlocked identity.'));
        request.onsuccess = () => {
            const value = request.result ?? null;

            if (
                ! value
                || ! value.privateKey
                || typeof value.privateKey !== 'object'
                || ! value.publicJwk
            ) {
                resolve(null);

                return;
            }

            resolve(value);
        };
    });
}

/**
 * Store a non-extractable identity CryptoKey via IndexedDB structured cloning.
 *
 * @param {string} browserDbId
 * @param {UnlockedIdentityRecord} identity
 * @returns {Promise<void>}
 */
export async function saveUnlockedIdentity(browserDbId, identity) {
    if (identity.privateKey.extractable) {
        throw new Error('Unlocked identity private keys must be non-extractable.');
    }

    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const request = identityStore(database, 'readwrite').put(identity, UNLOCKED_IDENTITY_KEY);

        request.onerror = () => reject(request.error ?? new Error('Failed to save unlocked identity.'));
        request.onsuccess = () => resolve();
    });
}

/**
 * Remove the unlocked identity CryptoKey (e.g. explicit logout / lock).
 *
 * @param {string} browserDbId
 * @returns {Promise<void>}
 */
export async function clearUnlockedIdentity(browserDbId) {
    const database = await openDatabase(databaseNameForBrowserDbId(browserDbId));

    return new Promise((resolve, reject) => {
        const request = identityStore(database, 'readwrite').delete(UNLOCKED_IDENTITY_KEY);

        request.onerror = () => reject(request.error ?? new Error('Failed to clear unlocked identity.'));
        request.onsuccess = () => resolve();
    });
}
