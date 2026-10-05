import { t } from './i18n';

export function afn(value) {
    const amount = Number(value || 0);
    const digits = Number.isInteger(amount) ? 0 : 2;

    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: digits,
        maximumFractionDigits: 2,
    }).format(amount);

    return `\u200E${formatted} اف`;
}

export function statusClass(status) {
    if (status === 'paid' || status === 'active') {
        return 'badge badge-paid';
    }

    if (status === 'unpaid') {
        return 'badge badge-unpaid';
    }

    if (status === 'inactive') {
        return 'badge badge-inactive';
    }

    return 'badge badge-inactive';
}

export function statusLabel(status) {
    return t(`status.${status}`) === `status.${status}` ? status : t(`status.${status}`);
}

export function fieldErrors(error) {
    const errors = error?.response?.data?.errors || {};

    return Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0]]));
}

export function errorMessage(error) {
    const errors = error?.response?.data?.errors;

    if (errors) {
        return Object.values(errors).flat()[0];
    }

    return error?.response?.data?.message || 'Something went wrong. Please try again.';
}

export function moneyClass(value) {
    const amount = Number(value || 0);

    if (amount > 0) return 'text-emerald-700';
    if (amount < 0) return 'text-rose-700';

    return 'text-slate-700';
}
