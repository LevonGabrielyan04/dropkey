import { decryptViaWorker } from '../decrypt/decryptViaWorker.js';
import { encryptViaWorker } from '../encrypt/encryptViaWorker.js';
import { deserializeIdentity, serializeIdentity } from './identitySerialization.js';
import {
    databaseNameForBrowserDbId,
    loadEncryptedIdentity,
    saveEncryptedIdentity,
} from './keyStore.js';

const SESSION_PASSWORD_KEY = 'passshare:account-password';
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
 * @returns {string|null}
 */
export function getSessionPassword() {
    return sessionStore().getItem(SESSION_PASSWORD_KEY);
}

/**
 * @param {string} password
 */
export function setSessionPassword(password) {
    sessionStore().setItem(SESSION_PASSWORD_KEY, password);
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

    sessionStore().removeItem(SESSION_PASSWORD_KEY);
    sessionStore().removeItem(SESSION_BROWSER_DB_ID_KEY);
}

/**
 * @param {string} browserDbId
 * @param {string} password
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function unlockIdentity(browserDbId, password) {
    const encrypted = await loadEncryptedIdentity(browserDbId);

    if (encrypted) {
        const plaintext = await decryptViaWorker(encrypted, password);

        cachedIdentity = await deserializeIdentity(plaintext);

        return cachedIdentity;
    }

    cachedIdentity = null;

    return null;
}

/**
 * @param {string} browserDbId
 * @param {string} password
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<void>}
 */
export async function persistIdentity(browserDbId, password, identity) {
    const plaintext = await serializeIdentity(identity);
    const encrypted = await encryptViaWorker(plaintext, password);

    await saveEncryptedIdentity(browserDbId, encrypted);
    cachedIdentity = identity;
}

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function loadIdentity() {
    if (cachedIdentity) {
        return cachedIdentity;
    }

    const browserDbId = resolveBrowserDbId();
    const password = getSessionPassword();

    if (! browserDbId || ! password) {
        return null;
    }

    try {
        return await unlockIdentity(browserDbId, password);
    } catch {
        cachedIdentity = null;

        return null;
    }
}

/**
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<void>}
 */
export async function saveIdentity(identity) {
    const browserDbId = resolveBrowserDbId();
    const password = getSessionPassword();

    if (! browserDbId || ! password) {
        throw new Error('Cannot save identity without a browser database id and session password.');
    }

    await persistIdentity(browserDbId, password, identity);
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
