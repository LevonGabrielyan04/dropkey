import { describe, expect, it } from 'vitest';

import { databaseNameForUser } from './keyStore.js';

describe('keyStore', () => {
    it('builds a per-user database name', () => {
        expect(databaseNameForUser('alice')).toBe('passshare-alice');
    });
});
