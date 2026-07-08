import {
    createIdentityEnvelope,
    ECDH_P256_ALGORITHM,
    IDENTITY_KEY_USAGES,
    toNonExtractableIdentityKey,
    unlockIdentityEnvelope,
} from '../core/cryptoUtils.js';
import { decryptViaWorker } from '../decrypt/decryptViaWorker.js';
import {
    clearUnlockedIdentity,
    databaseNameForBrowserDbId,
    isIdentityEnvelope,
    isLegacyEncryptedIdentity,
    loadEncryptedIdentity,
    loadUnlockedIdentity,
    saveEncryptedIdentity,
    saveUnlockedIdentity,
} from './keyStore.js';

const SESSION_BROWSER_DB_ID_KEY = 'passshare:browser-db-id';

/** @type {Map<string, string>} */
const memorySessionStore = new Map();

/**
 * @returns {Pick<Storage, 'getItem' | 'setItem' | 'removeItem'>}
 */
function sessionStore() {
    if (typeof sessionStorage !== 'undefined') {
        return sessionStorage;
    }

    return {
        getItem: (key) => memorySessionStore.get(key) ?? null,
        setItem: (key, value) => {
            memorySessionStore.set(key, value);
        },
        removeItem: (key) => {
            memorySessionStore.delete(key);
        },
    };
}

/** @type {{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null} */
let cachedIdentity = null;

/**
 * @returns {{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null}
 */
export function getCachedIdentity() {
    return cachedIdentity;
}

/**
 * @param {string} browserDbId
 */
export function setSessionBrowserDbId(browserDbId) {
    sessionStore().setItem(SESSION_BROWSER_DB_ID_KEY, browserDbId);
}

/**
 * @returns {string|null}
 */
export function getSessionBrowserDbId() {
    return sessionStore().getItem(SESSION_BROWSER_DB_ID_KEY);
}

export function clearCachedIdentity() {
    cachedIdentity = null;
}

export function clearSessionCredentials() {
    clearCachedIdentity();

    sessionStore().removeItem(SESSION_BROWSER_DB_ID_KEY);
}

/**
 * @param {string} browserDbId
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
async function cacheAndPersistUnlockedIdentity(browserDbId, identity) {
    await saveUnlockedIdentity(browserDbId, identity);
    cachedIdentity = identity;

    return identity;
}

/**
 * Persist a non-extractable unlocked CryptoKey without a password-protected envelope.
 *
 * @param {string} browserDbId
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
export async function persistUnlockedIdentity(browserDbId, identity) {
    const unlockedIdentity = {
        privateKey: await toNonExtractableIdentityKey(identity.privateKey),
        publicJwk: identity.publicJwk,
    };

    return cacheAndPersistUnlockedIdentity(browserDbId, unlockedIdentity);
}

/**
 * Migrate a legacy Argon2 identity blob to a v2 KEK/DEK envelope.
 *
 * @param {string} browserDbId
 * @param {string} password
 * @param {{ ciphertext: string, salt: string, iv: string }} legacy
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
async function migrateLegacyIdentity(browserDbId, password, legacy) {
    const plaintext = await decryptViaWorker(legacy, password);
    const { publicJwk, privateJwk } = JSON.parse(plaintext);

    // Import extractable so wrapKey can build the v2 envelope; unlocked storage uses non-extractable.
    const privateKey = await globalThis.crypto.subtle.importKey(
        'jwk',
        privateJwk,
        ECDH_P256_ALGORITHM,
        true,
        IDENTITY_KEY_USAGES,
    );

    return persistIdentity(browserDbId, password, {
        privateKey,
        publicJwk,
    });
}

/**
 * Unlock with password: prefers v2 envelope, migrates legacy blobs, and always
 * persists a non-extractable CryptoKey for passwordless page reloads.
 *
 * @param {string} browserDbId
 * @param {string} password
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function unlockIdentity(browserDbId, password) {
    const encrypted = await loadEncryptedIdentity(browserDbId);

    if (! encrypted) {
        cachedIdentity = null;

        return null;
    }

    if (isIdentityEnvelope(encrypted)) {
        const identity = await unlockIdentityEnvelope(password, encrypted);

        return cacheAndPersistUnlockedIdentity(browserDbId, identity);
    }

    if (isLegacyEncryptedIdentity(encrypted)) {
        return migrateLegacyIdentity(browserDbId, password, encrypted);
    }

    throw new Error('Unsupported identity storage format.');
}

/**
 * Persist a new KEK/DEK envelope and the mandatory unlocked CryptoKey in IndexedDB.
 *
 * @param {string} browserDbId
 * @param {string} password
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }>}
 */
export async function persistIdentity(browserDbId, password, identity) {
    const { envelope, unlockedPrivateKey } = await createIdentityEnvelope(password, identity);
    const unlockedIdentity = {
        privateKey: unlockedPrivateKey,
        publicJwk: identity.publicJwk,
    };

    await saveEncryptedIdentity(browserDbId, envelope);

    return cacheAndPersistUnlockedIdentity(browserDbId, unlockedIdentity);
}

/**
 * Load identity without prompting for a password when an unlocked CryptoKey exists.
 *
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function loadIdentity() {
    if (cachedIdentity) {
        return cachedIdentity;
    }

    const browserDbId = resolveBrowserDbId();

    if (! browserDbId) {
        return null;
    }

    try {
        const unlocked = await loadUnlockedIdentity(browserDbId);

        if (unlocked) {
            cachedIdentity = unlocked;

            return unlocked;
        }
    } catch {
        // IndexedDB structured-clone load failed; no password fallback exists.
    }

    return null;
}

/**
 * Persist the current identity as an unlocked CryptoKey for the active browser DB.
 *
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<void>}
 */
export async function saveIdentity(identity) {
    const browserDbId = resolveBrowserDbId();

    if (! browserDbId) {
        throw new Error('Cannot save identity without a browser database id.');
    }

    await persistUnlockedIdentity(browserDbId, identity);
}

/**
 * Clear in-memory and IndexedDB unlocked key material for the current browser DB.
 *
 * @returns {Promise<void>}
 */
export async function lockIdentity() {
    const browserDbId = resolveBrowserDbId();

    clearCachedIdentity();

    if (! browserDbId) {
        return;
    }

    await clearUnlockedIdentity(browserDbId);
}

/**
 * @returns {string|null}
 */
export function resolveBrowserDbId() {
    if (typeof document !== 'undefined') {
        const datasetBrowserDbId = document.body?.dataset?.browserDbId;

        if (datasetBrowserDbId) {
            return datasetBrowserDbId;
        }
    }

    return getSessionBrowserDbId();
}

export { databaseNameForBrowserDbId };
