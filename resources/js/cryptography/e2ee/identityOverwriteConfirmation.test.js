import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import {
    confirmIdentityKeyOverwrite,
    settleIdentityKeyOverwriteConfirmation,
} from './identityOverwriteConfirmation.js';

describe('identityOverwriteConfirmation', () => {
    /** @type {((event: { detail: boolean }) => void)|undefined} */
    let responseHandler;

    beforeEach(() => {
        responseHandler = undefined;

        vi.stubGlobal('window', {
            addEventListener: vi.fn((event, handler) => {
                if (event === 'passshare:identity-key-overwrite-response') {
                    responseHandler = handler;
                }
            }),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('requests confirmation before resolving', async () => {
        const confirmation = confirmIdentityKeyOverwrite();

        expect(window.dispatchEvent).toHaveBeenCalledWith(
            expect.objectContaining({ type: 'passshare:identity-key-overwrite-request' }),
        );

        responseHandler?.({ detail: true });

        await expect(confirmation).resolves.toBe(true);
    });

    it('resolves false when overwrite is cancelled', async () => {
        const confirmation = confirmIdentityKeyOverwrite();

        responseHandler?.({ detail: false });

        await expect(confirmation).resolves.toBe(false);
    });

    it('dispatches a response event when settled', () => {
        settleIdentityKeyOverwriteConfirmation(true);

        expect(window.dispatchEvent).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'passshare:identity-key-overwrite-response',
                detail: true,
            }),
        );
    });
});
