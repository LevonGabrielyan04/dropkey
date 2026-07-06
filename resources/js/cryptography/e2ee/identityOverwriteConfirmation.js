const REQUEST_EVENT = 'passshare:identity-key-overwrite-request';
const RESPONSE_EVENT = 'passshare:identity-key-overwrite-response';

/**
 * @returns {Promise<boolean>}
 */
export function confirmIdentityKeyOverwrite() {
    return new Promise((resolve) => {
        if (typeof window === 'undefined') {
            resolve(false);

            return;
        }

        const handleResponse = (event) => {
            window.removeEventListener(RESPONSE_EVENT, handleResponse);
            resolve(Boolean(event.detail));
        };

        window.addEventListener(RESPONSE_EVENT, handleResponse);
        window.dispatchEvent(new CustomEvent(REQUEST_EVENT));
    });
}

/**
 * @param {boolean} confirmed
 */
export function settleIdentityKeyOverwriteConfirmation(confirmed) {
    if (typeof window === 'undefined') {
        return;
    }

    window.dispatchEvent(new CustomEvent(RESPONSE_EVENT, { detail: confirmed }));
}
