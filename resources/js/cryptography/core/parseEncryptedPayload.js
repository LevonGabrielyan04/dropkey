/**
 * Detect client-side encrypted payloads stored as JSON strings.
 *
 * @param {unknown} message
 * @returns {{ ciphertext: string, salt: string, iv: string } | null}
 */
export function parseEncryptedPayload(message) {
    if (typeof message !== 'string' || message === '') {
        return null;
    }

    try {
        const parsed = JSON.parse(message);

        if (
            parsed
            && typeof parsed.ciphertext === 'string'
            && typeof parsed.salt === 'string'
            && typeof parsed.iv === 'string'
        ) {
            return parsed;
        }
    } catch {
        return null;
    }

    return null;
}
