import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** Merge Tailwind classes safely. */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

/** Arabic-Indic-aware number formatting (kept Western digits, grouped). */
export function num(n) {
    if (n === null || n === undefined || n === '') return '—';
    return new Intl.NumberFormat('ar-SA', { maximumFractionDigits: 1 }).format(n);
}

/** Cap a badge count at 99+. */
export function formatBadge(n) {
    if (!n) return null;
    return n > 99 ? '+99' : String(n);
}

/** Simple initials from a name. */
export function initials(name) {
    if (!name) return '؟';
    const parts = String(name).trim().split(/\s+/);
    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
}
