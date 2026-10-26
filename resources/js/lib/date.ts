export const DISPLAY_TIMEZONE = 'Asia/Jakarta';

const isoFormatter = new Intl.DateTimeFormat('en-CA', {
    timeZone: DISPLAY_TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});

function dateFromIso(date: string): Date {
    return new Date(`${date}T00:00:00Z`);
}

export function todayInAppTimezone(): string {
    const parts = isoFormatter.formatToParts(new Date());
    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

    return `${values.year}-${values.month}-${values.day}`;
}

export function formatMonth(month: string): string {
    return dateFromIso(`${month}-01`).toLocaleDateString('id-ID', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

export function formatDay(date: string): string {
    return dateFromIso(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

export function formatDateShort(date: string): string {
    return dateFromIso(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        timeZone: 'UTC',
    });
}

export function formatWeekday(date: string): string {
    return dateFromIso(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        timeZone: 'UTC',
    });
}

export function formatTime(isoDate: string): string {
    return new Date(isoDate).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: DISPLAY_TIMEZONE,
    });
}

export function addDays(date: string, days: number): string {
    const next = dateFromIso(date);
    next.setUTCDate(next.getUTCDate() + days);

    return next.toISOString().slice(0, 10);
}

export function addMonths(month: string, delta: number): string {
    const [year, monthNumber] = month.split('-').map(Number);
    const next = new Date(Date.UTC(year, monthNumber - 1 + delta, 1));

    return `${next.getUTCFullYear()}-${String(next.getUTCMonth() + 1).padStart(2, '0')}`;
}

export function isWeekend(date: string): boolean {
    const weekday = dateFromIso(date).getUTCDay();

    return weekday === 0 || weekday === 6;
}

export function dayNumber(date: string): number {
    return dateFromIso(date).getUTCDate();
}
