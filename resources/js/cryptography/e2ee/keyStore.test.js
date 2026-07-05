import { beforeEach, describe, expect, it, vi } from 'vitest';

const deleteDatabase = vi.hoisted(() => vi.fn());
const listDatabases = vi.hoisted(() => vi.fn(async () => [
    { name: 'passshare-e2ee' },
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

import { clearAllIndexedDB } from './keyStore.js';

describe('keyStore clearAllIndexedDB', () => {
    beforeEach(() => {
        deleteDatabase.mockClear();
        listDatabases.mockClear();
        indexedDB.databases = listDatabases;
    });

    it('deletes every database returned by indexedDB.databases()', async () => {
        await clearAllIndexedDB();

        expect(listDatabases).toHaveBeenCalledOnce();
        expect(deleteDatabase).toHaveBeenCalledTimes(2);
        expect(deleteDatabase).toHaveBeenCalledWith('passshare-e2ee');
        expect(deleteDatabase).toHaveBeenCalledWith('other-app-db');
    });

    it('falls back to the PassShare database when indexedDB.databases is unavailable', async () => {
        indexedDB.databases = undefined;

        await clearAllIndexedDB();

        expect(deleteDatabase).toHaveBeenCalledOnce();
        expect(deleteDatabase).toHaveBeenCalledWith('passshare-e2ee');
    });

    it('resolves when a database deletion is blocked', async () => {
        deleteDatabase.mockImplementationOnce((name) => {
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
