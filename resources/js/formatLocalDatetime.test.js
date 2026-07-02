import { describe, expect, it } from 'vitest';
import { formatLocalDatetime } from './formatLocalDatetime.js';

describe('formatLocalDatetime', () => {
    it('returns an empty string for invalid input', () => {
        expect(formatLocalDatetime('')).toBe('');
        expect(formatLocalDatetime('not-a-date')).toBe('');
    });

    it('formats a UTC datetime in the requested timezone', () => {
        expect(formatLocalDatetime('2026-07-02T18:30:00Z', 'America/New_York', 'en-US'))
            .toBe('Jul 2, 2026 2:30 PM');
    });

    it('formats a UTC datetime in the requested timezone across a date boundary', () => {
        expect(formatLocalDatetime('2026-07-03T02:15:00Z', 'America/Los_Angeles', 'en-US'))
            .toBe('Jul 2, 2026 7:15 PM');
    });
});
