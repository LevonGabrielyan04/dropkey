import { beforeEach, describe, expect, it, vi } from 'vitest';

const clearAllIndexedDB = vi.hoisted(() => vi.fn(async () => {}));
const clearSessionCredentials = vi.hoisted(() => vi.fn());

vi.mock('./cryptography/e2ee/keyStore.js', () => ({
    clearAllIndexedDB,
}));

vi.mock('./cryptography/e2ee/identitySession.js', () => ({
    clearSessionCredentials,
}));

import { bindLogoutIndexedDbCleanup } from './logoutCleanup.js';

describe('logout IndexedDB cleanup', () => {
    beforeEach(() => {
        clearAllIndexedDB.mockClear();
        clearSessionCredentials.mockClear();
    });

    it('clears IndexedDB before submitting a logout form', async () => {
        const doc = {
            addEventListener: vi.fn((type, listener, options) => {
                doc.submitListener = listener;
                doc.submitListenerOptions = options;
            }),
        };

        bindLogoutIndexedDbCleanup(doc);

        const form = {
            getAttribute: vi.fn((name) => (name === 'action' ? '/logout' : null)),
            hasAttribute: vi.fn(() => false),
            dataset: {},
            submit: vi.fn(),
        };

        const event = {
            target: form,
            preventDefault: vi.fn(),
        };

        doc.submitListener(event);

        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(clearSessionCredentials).toHaveBeenCalledOnce();
        expect(clearAllIndexedDB).toHaveBeenCalledOnce();
        expect(doc.submitListenerOptions).toBe(true);

        await vi.waitFor(() => {
            expect(form.dataset.indexedDbCleared).toBe('true');
            expect(form.submit).toHaveBeenCalledOnce();
        });
    });

    it('does not intercept logout forms that were already cleared', () => {
        const doc = {
            addEventListener: vi.fn((type, listener) => {
                doc.submitListener = listener;
            }),
        };

        bindLogoutIndexedDbCleanup(doc);

        const form = {
            getAttribute: vi.fn((name) => (name === 'action' ? '/logout' : null)),
            hasAttribute: vi.fn(() => false),
            dataset: { indexedDbCleared: 'true' },
            submit: vi.fn(),
        };

        const event = {
            target: form,
            preventDefault: vi.fn(),
        };

        doc.submitListener(event);

        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(clearSessionCredentials).not.toHaveBeenCalled();
        expect(clearAllIndexedDB).not.toHaveBeenCalled();
        expect(form.submit).not.toHaveBeenCalled();
    });

    it('clears IndexedDB without blocking Livewire submit handlers', () => {
        const doc = {
            addEventListener: vi.fn((type, listener) => {
                doc.submitListener = listener;
            }),
        };

        bindLogoutIndexedDbCleanup(doc);

        const form = {
            getAttribute: vi.fn(() => null),
            hasAttribute: vi.fn((name) => name === 'wire:submit' || name === 'data-clear-indexeddb-on-submit'),
            dataset: {},
            submit: vi.fn(),
        };

        const event = {
            target: form,
            preventDefault: vi.fn(),
        };

        doc.submitListener(event);

        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(clearSessionCredentials).toHaveBeenCalledOnce();
        expect(clearAllIndexedDB).toHaveBeenCalledOnce();
        expect(form.submit).not.toHaveBeenCalled();
    });

    it('clears IndexedDB for forms marked with data-clear-indexeddb-on-submit', () => {
        const doc = {
            addEventListener: vi.fn((type, listener) => {
                doc.submitListener = listener;
            }),
        };

        bindLogoutIndexedDbCleanup(doc);

        const form = {
            getAttribute: vi.fn(() => null),
            hasAttribute: vi.fn((name) => name === 'data-clear-indexeddb-on-submit'),
            dataset: {},
            submit: vi.fn(),
        };

        const event = {
            target: form,
            preventDefault: vi.fn(),
        };

        doc.submitListener(event);

        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(clearSessionCredentials).toHaveBeenCalledOnce();
        expect(clearAllIndexedDB).toHaveBeenCalledOnce();
    });
});
