const numberFormat = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/** Prices are in Bangladeshi Taka. */
export function money(value) {
    if (value === null || value === undefined || value === '') return '—';
    return `৳${numberFormat.format(Number(value))}`;
}

export function date(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

/** Simple debounce for search inputs. */
export function debounce(fn, wait = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
    };
}
