import { ensureServerIdentityKey } from './cryptography/e2ee/identity.js';
import { setSessionBrowserDbId } from './cryptography/e2ee/identitySession.js';

/**
 * Bootstrap E2EE identity registration for authenticated app pages.
 * Runs after login or registration redirects into the workspace.
 *
 * @param {Document} [doc]
 */
export async function bootstrapIdentityRegistration(doc = globalThis.document) {
    if (! doc?.body) {
        return;
    }

    const { identityRegisterUrl, identityMineUrl, csrfToken, browserDbId } = doc.body.dataset ?? {};

    if (! identityRegisterUrl || ! identityMineUrl || ! csrfToken) {
        return;
    }

    if (browserDbId) {
        setSessionBrowserDbId(browserDbId);
    }

    await ensureServerIdentityKey({
        registerUrl: identityRegisterUrl,
        mineUrl: identityMineUrl,
        csrfToken,
    });
}

if (typeof document !== 'undefined') {
    const runBootstrap = () => {
        bootstrapIdentityRegistration().catch(() => {
            // Chat sessions retry registration when needed.
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runBootstrap);
    } else {
        runBootstrap();
    }
}
