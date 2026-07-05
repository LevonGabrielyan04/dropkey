import { beforeEach, describe, expect, it, vi } from 'vitest';

const setSessionPassword = vi.hoisted(() => vi.fn());

vi.mock('./cryptography/e2ee/identitySession.js', () => ({
    setSessionPassword,
}));

import { bindAuthCredentialCapture } from './authCredentialCapture.js';

describe('auth credential capture', () => {
    beforeEach(() => {
        setSessionPassword.mockClear();
    });

    it('stores the password when a login form is submitted', () => {
        const doc = {
            addEventListener: vi.fn((type, listener, options) => {
                doc.submitListener = listener;
                doc.submitListenerOptions = options;
            }),
        };

        bindAuthCredentialCapture(doc);

        const form = {
            getAttribute: vi.fn((name) => (name === 'action' ? '/login' : null)),
            querySelector: vi.fn(() => ({ value: 'secret-password' })),
        };

        doc.submitListener({ target: form });

        expect(setSessionPassword).toHaveBeenCalledWith('secret-password');
        expect(doc.submitListenerOptions).toBe(true);
    });

    it('stores the password when a register form is submitted', () => {
        const doc = {
            addEventListener: vi.fn((type, listener) => {
                doc.submitListener = listener;
            }),
        };

        bindAuthCredentialCapture(doc);

        const form = {
            getAttribute: vi.fn((name) => (name === 'action' ? '/register' : null)),
            querySelector: vi.fn(() => ({ value: 'new-password' })),
        };

        doc.submitListener({ target: form });

        expect(setSessionPassword).toHaveBeenCalledWith('new-password');
    });

    it('ignores unrelated form submissions', () => {
        const doc = {
            addEventListener: vi.fn((type, listener) => {
                doc.submitListener = listener;
            }),
        };

        bindAuthCredentialCapture(doc);

        const form = {
            getAttribute: vi.fn((name) => (name === 'action' ? '/dashboard' : null)),
            querySelector: vi.fn(),
        };

        doc.submitListener({ target: form });

        expect(setSessionPassword).not.toHaveBeenCalled();
        expect(form.querySelector).not.toHaveBeenCalled();
    });
});
