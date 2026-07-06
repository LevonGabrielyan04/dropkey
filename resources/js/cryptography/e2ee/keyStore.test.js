import { describe, expect, it } from 'vitest';

import { databaseNameForBrowserDbId } from './keyStore.js';

describe('keyStore', () => {
    it('builds a per-browser database name', () => {
        expect(databaseNameForBrowserDbId('01JABCDEF1234567890ABCDEFGH')).toBe('passshare-01JABCDEF1234567890ABCDEFGH');
    });
});
