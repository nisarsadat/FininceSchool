const BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

function div(a, b) {
    return Math.trunc(a / b);
}

function mod(a, b) {
    return a - Math.trunc(a / b) * b;
}

function jalCalLeap(jy) {
    let jp = BREAKS[0];
    let jump = 0;

    for (let i = 1; i < BREAKS.length; i += 1) {
        const jm = BREAKS[i];
        jump = jm - jp;

        if (jy < jm) {
            break;
        }

        jp = jm;
    }

    let n = jy - jp;

    if (jump - n < 6) {
        n = n - jump + div(jump + 4, 33) * 33;
    }

    let leap = mod(mod(n + 1, 33) - 1, 4);

    if (leap === -1) {
        leap = 4;
    }

    return leap;
}

export function isLeap(year) {
    return jalCalLeap(year) === 0;
}

export function monthLength(year, month) {
    if (month <= 6) return 31;
    if (month <= 11) return 30;

    return isLeap(year) ? 30 : 29;
}

export function blankDate(today) {
    return {
        year: today?.year || 1405,
        month: today?.month || 1,
        day: today?.day || 1,
    };
}
