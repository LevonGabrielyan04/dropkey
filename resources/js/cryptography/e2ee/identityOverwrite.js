import { confirmIdentityKeyOverwrite } from './identityOverwriteConfirmation.js';
import {
    getSessionPassword,
    resolveBrowserDbId,
    unlockIdentity,
} from './identitySession.js';
import { loadEncryptedIdentity, loadUnlockedIdentity } from './keyStore.js';

export class IdentityKeyOverwriteCancelledError extends Error {
    constructor() {
        super('Identity key overwrite was cancelled.');

        this.name = 'IdentityKeyOverwriteCancelledError';
    }
}

/**
 * @param {string|null|undefined} browserDbId
 * @returns {Promise<boolean>}
 */
export async function wouldOverwriteLocalIdentity(browserDbId) {
    if (! browserDbId) {
        return false;
    }

    const unlocked = await loadUnlockedIdentity(browserDbId);

    if (unlocked) {
        return false;
    }

    const encrypted = await loadEncryptedIdentity(browserDbId);

    if (! encrypted) {
        return false;
    }

    const password = getSessionPassword();

    if (! password) {
        return true;
    }

    try {
        const unlockedWithPassword = await unlockIdentity(browserDbId, password);

        return unlockedWithPassword === null;
    } catch {
        return true;
    }
}

/**
 * @param {string} mineUrl
 * @param {string} fingerprint
 * @returns {Promise<boolean>}
 */
export async function wouldOverwriteServerIdentity(mineUrl, fingerprint) {
    const response = await fetch(mineUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        return false;
    }

    const mine = await response.json();

    return Boolean(mine.registered) && mine.fingerprint !== fingerprint;
}

/**
 * @param {object} [options]
 * @param {boolean} [options.checkLocal=true]
 * @param {boolean} [options.checkServer=false]
 * @param {string|null} [options.browserDbId]
 * @param {string|null} [options.mineUrl]
 * @param {string|null} [options.fingerprint]
 * @returns {Promise<void>}
 */
export async function ensureIdentityOverwriteAllowed({
    checkLocal = true,
    checkServer = false,
    browserDbId = resolveBrowserDbId(),
    mineUrl = null,
    fingerprint = null,
} = {}) {
    const localOverwrite = checkLocal && await wouldOverwriteLocalIdentity(browserDbId);
    const serverOverwrite = checkServer
        && mineUrl
        && fingerprint
        && await wouldOverwriteServerIdentity(mineUrl, fingerprint);

    if (! localOverwrite && ! serverOverwrite) {
        return;
    }

    const confirmed = await confirmIdentityKeyOverwrite();

    if (! confirmed) {
        throw new IdentityKeyOverwriteCancelledError();
    }
}
