import { beforeEach, describe, expect, it, vi } from 'vitest';

const deleteDatabase = vi.hoisted(() => vi.fn());
const listDatabases = vi.hoisted(() => vi.fn(async () => [
    { name: 'passshare-alice' },
    { name: 'passshare-bob' },
    { name: 'other-app-db' },
]));

vi.hoisted(() => {
    globalThis.indexedDB = {
        databases: listDatabases,
        deleteDatabase,
    };

    deleteDatabase.mockImplementation(() => {
        const request = {
            onsuccess: null,
            onerror: null,
            onblocked: null,
        };

        queueMicrotask(() => request.onsuccess?.());

        return request;
    });
});

import { clearAllIndexedDB, databaseNameForUser } from './keyStore.js';

describe('keyStore', () => {
    beforeEach(() => {
        deleteDatabase.mockClear();
        listDatabases.mockClear();
        indexedDB.databases = listDatabases;
    });

    it('builds a per-user database name', () => {
        expect(databaseNameForUser('alice')).toBe('passshare-alice');
    });

    it('deletes every PassShare database returned by indexedDB.databases()', async () => {
        await clearAllIndexedDB();

        expect(listDatabases).toHaveBeenCalledOnce();
        expect(deleteDatabase).toHaveBeenCalledTimes(2);
        expect(deleteDatabase).toHaveBeenCalledWith('passshare-alice');
        expect(deleteDatabase).toHaveBeenCalledWith('passshare-bob');
    });

    it('does nothing when indexedDB.databases is unavailable', async () => {
        indexedDB.databases = undefined;

        await clearAllIndexedDB();

        expect(deleteDatabase).not.toHaveBeenCalled();
    });

    it('resolves when a database deletion is blocked', async () => {
        deleteDatabase.mockImplementationOnce(() => {
            const request = {
                onsuccess: null,
                onerror: null,
                onblocked: null,
            };

            queueMicrotask(() => request.onblocked?.());

            return request;
        });

        await expect(clearAllIndexedDB()).resolves.toBeUndefined();
    });
});
