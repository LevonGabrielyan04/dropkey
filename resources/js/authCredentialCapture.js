import { setTransientAccountPassword } from './cryptography/e2ee/identitySession.js';

/**
 * @param {HTMLFormElement|null} form
 */
function shouldCaptureCredentials(form) {
    if (! form || typeof form.getAttribute !== 'function') {
        return false;
    }

    const action = form.getAttribute('action') ?? '';

    return action.includes('/login') || action.includes('/register');
}

/**
 * @param {HTMLFormElement} form
 */
function captureCredentialsFromForm(form) {
    const passwordInput = form.querySelector('[name="password"]');

    if (! passwordInput || typeof passwordInput.value !== 'string' || passwordInput.value === '') {
        return;
    }

    setTransientAccountPassword(passwordInput.value);
}

/**
 * Keep the account password in memory only long enough to unlock stored identity keys.
 *
 * @param {Document} [doc]
 */
export function bindAuthCredentialCapture(doc = globalThis.document) {
    if (! doc) {
        return;
    }

    doc.addEventListener('submit', (event) => {
        const form = event.target;

        if (! shouldCaptureCredentials(form)) {
            return;
        }

        captureCredentialsFromForm(form);
    }, true);
}

if (typeof document !== 'undefined') {
    bindAuthCredentialCapture();
}
