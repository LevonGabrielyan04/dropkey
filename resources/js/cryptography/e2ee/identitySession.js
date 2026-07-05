import { decryptViaWorker } from '../decrypt/decryptViaWorker.js';
import { encryptViaWorker } from '../encrypt/encryptViaWorker.js';
import { deserializeIdentity, serializeIdentity } from './identitySerialization.js';
import {
    databaseNameForUser,
    loadEncryptedIdentity,
    saveEncryptedIdentity,
} from './keyStore.js';

const SESSION_PASSWORD_KEY = 'passshare:account-password';
const SESSION_USERNAME_KEY = 'passshare:username';

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
 * @param {string} username
 */
export function setSessionUsername(username) {
    sessionStore().setItem(SESSION_USERNAME_KEY, username);
}

/**
 * @returns {string|null}
 */
export function getSessionUsername() {
    return sessionStore().getItem(SESSION_USERNAME_KEY);
}

export function clearCachedIdentity() {
    cachedIdentity = null;
}

export function clearSessionCredentials() {
    clearCachedIdentity();

    sessionStore().removeItem(SESSION_PASSWORD_KEY);
    sessionStore().removeItem(SESSION_USERNAME_KEY);
}

/**
 * @param {string} username
 * @param {string} password
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function unlockIdentity(username, password) {
    const encrypted = await loadEncryptedIdentity(username);

    if (encrypted) {
        const plaintext = await decryptViaWorker(encrypted, password);

        cachedIdentity = await deserializeIdentity(plaintext);

        return cachedIdentity;
    }

    cachedIdentity = null;

    return null;
}

/**
 * @param {string} username
 * @param {string} password
 * @param {{ privateKey: CryptoKey, publicJwk: JsonWebKey }} identity
 * @returns {Promise<void>}
 */
export async function persistIdentity(username, password, identity) {
    const plaintext = await serializeIdentity(identity);
    const encrypted = await encryptViaWorker(plaintext, password);

    await saveEncryptedIdentity(username, encrypted);
    cachedIdentity = identity;
}

/**
 * @returns {Promise<{ privateKey: CryptoKey, publicJwk: JsonWebKey }|null>}
 */
export async function loadIdentity() {
    if (cachedIdentity) {
        return cachedIdentity;
    }

    const username = resolveUsername();
    const password = getSessionPassword();

    if (! username || ! password) {
        return null;
    }

    try {
        return await unlockIdentity(username, password);
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
    const username = resolveUsername();
    const password = getSessionPassword();

    if (! username || ! password) {
        throw new Error('Cannot save identity without an authenticated session password.');
    }

    await persistIdentity(username, password, identity);
}

/**
 * @returns {string|null}
 */
function resolveUsername() {
    if (typeof document !== 'undefined') {
        const datasetUsername = document.body?.dataset?.username;

        if (datasetUsername) {
            return datasetUsername;
        }
    }

    return getSessionUsername();
}

export { databaseNameForUser };
