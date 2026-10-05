// Runs the real `items` computed out of Pagination.vue, not a copy of it, so the
// test cannot silently drift away from the component. The script block is pulled
// out of the .vue file, the Vue imports and defineProps are stubbed, and the
// resulting function is executed over every page/last combination.
import fs from 'node:fs';

const source = fs.readFileSync('resources/js/components/Pagination.vue', 'utf8');
// Strip the ESM imports: the harness below supplies its own stubs, and
// `new Function` cannot host a static import statement.
const script = source
    .split('<script setup>')[1]
    .split('</script>')[0]
    .replace(/^import .*$/gm, '');

const harness = `
const computed = (fn) => ({ get value() { return fn(); } });
const ref = (value) => ({ value });
const watch = () => () => {};
const onBeforeUnmount = () => {};
const injected = {};
const defineProps = () => injected;
const defineEmits = () => () => {};
const t = (k) => k;
const perPageOptions = [10, 25, 50, 100];
const store = { locale: 'fa' };
const document = { addEventListener() {}, removeEventListener() {} };
${script}
return {
    read(last, current) {
        injected.pages = last;
        injected.page = current;

        return items.value;
    },
};
`;

const read = new Function(harness)().read;
let failures = 0;

function check(last, current) {
    const list = read(last, current);
    const label = list.map((i) => i.label).join(' ');
    const pages = list.filter((i) => i.page !== null).map((i) => i.page);
    const gaps = list.filter((i) => i.page === null).length;

    const problems = [];
    if (!pages.includes(1)) problems.push('missing first page');
    if (!pages.includes(last)) problems.push('missing last page');
    if (!pages.includes(current)) problems.push('missing current page');
    if (new Set(pages).size !== pages.length) problems.push('duplicate page numbers');
    for (let i = 1; i < pages.length; i += 1) {
        if (pages[i] <= pages[i - 1]) problems.push('not ascending');
    }
    if (list.some((i) => i.page !== null && (i.page < 1 || i.page > last))) {
        problems.push('page number out of range');
    }
    if (list.length > 7) problems.push(`${list.length} slots exceeds the 7-slot cap`);
    if (gaps > 2) problems.push(`${gaps} gaps`);
    // A gap must sit between two real numbers that are actually further apart.
    for (let i = 0; i < list.length; i += 1) {
        if (list[i].page !== null) continue;
        const before = list[i - 1];
        const after = list[i + 1];
        if (!before || !after) {
            problems.push('gap at the edge instead of a number');
        } else if (after.page - before.page <= 1) {
            problems.push('gap hides a single page');
        }
    }

    if (problems.length) {
        failures += 1;
        console.log(`FAIL  last=${last} cur=${current}  ${label}  -> ${problems.join('; ')}`);
    }

    return label;
}

console.log('representative cases:');
for (const [last, current] of [[1, 1], [2, 1], [3, 2], [5, 3], [7, 1], [7, 4], [7, 7], [9, 1], [40, 1], [40, 4], [40, 20], [40, 37], [40, 40]]) {
    console.log(`  last=${String(last).padStart(2)} cur=${String(current).padStart(2)}  ${check(last, current)}`);
}

console.log('\nsweeping every (last, current) pair from 1 to 60 ...');
for (let last = 1; last <= 60; last += 1) {
    for (let current = 1; current <= last; current += 1) {
        check(last, current);
    }
}
console.log('  sweep complete');

console.log(failures === 0 ? '\nALL PASS' : `\n${failures} FAILURES`);
process.exit(failures === 0 ? 0 : 1);