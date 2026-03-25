import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function formatVnd(value: unknown): string {
    const parsed = Number(value);
    const normalized = Number.isNaN(parsed) ? 0 : Math.max(0, Math.trunc(parsed));
    return `${new Intl.NumberFormat('en-US').format(normalized)} đ`;
}
