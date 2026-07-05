import { clearAllIndexedDB } from './cryptography/e2ee/keyStore.js';

/**
 * @param {EventTarget|null} form
 */
function shouldClearIndexedDB(form) {
    if (! form || typeof form.getAttribute !== 'function' || typeof form.hasAttribute !== 'function') {
        return false;
    }

    if (form.hasAttribute('data-clear-indexeddb-on-submit')) {
        return true;
    }

    const action = form.getAttribute('action') ?? '';

    return action.includes('/logout');
}

/**
 * Clear local cryptographic storage before the session ends.
 *
 * @param {Document} [doc]
 */
export function bindLogoutIndexedDbCleanup(doc = globalThis.document) {
    if (! doc) {
        return;
    }

    doc.addEventListener('submit', (event) => {
        const form = event.target;

        if (! shouldClearIndexedDB(form)) {
            return;
        }

        if (form.hasAttribute('wire:submit')) {
            clearAllIndexedDB().catch(() => {});

            return;
        }

        if (form.dataset.indexedDbCleared === 'true') {
            return;
        }

        event.preventDefault();

        form.dataset.indexedDbCleared = 'true';

        clearAllIndexedDB()
            .catch(() => {})
            .finally(() => form.submit());
    }, true);
}

if (typeof document !== 'undefined') {
    bindLogoutIndexedDbCleanup();
}
