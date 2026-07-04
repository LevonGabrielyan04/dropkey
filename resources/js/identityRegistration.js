import { ensureServerIdentityKey } from './cryptography/e2ee/identity.js';

/**
 * Bootstrap E2EE identity registration for authenticated app pages.
 * Runs after login or registration redirects into the workspace.
 *
 * @param {Document} [doc]
 */
export function bootstrapIdentityRegistration(doc = globalThis.document) {
    if (! doc?.body) {
        return;
    }

    const { identityRegisterUrl, identityMineUrl, csrfToken } = doc.body.dataset ?? {};

    if (! identityRegisterUrl || ! identityMineUrl || ! csrfToken) {
        return;
    }

    ensureServerIdentityKey({
        registerUrl: identityRegisterUrl,
        mineUrl: identityMineUrl,
        csrfToken,
    }).catch(() => {
        // Chat sessions retry registration when needed.
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => bootstrapIdentityRegistration());
    } else {
        bootstrapIdentityRegistration();
    }
}
