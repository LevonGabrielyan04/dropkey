/**
 * @param {string} utcDatetime
 * @param {string | undefined} timeZone
 * @param {string | undefined} locale
 */
export function formatLocalDatetime(utcDatetime, timeZone = undefined, locale = undefined) {
    const date = new Date(utcDatetime);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const formatter = new Intl.DateTimeFormat(locale, {
        timeZone,
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });

    const values = Object.fromEntries(
        formatter.formatToParts(date)
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, part.value]),
    );

    return `${values.month} ${values.day}, ${values.year} ${values.hour}:${values.minute} ${values.dayPeriod}`;
}
