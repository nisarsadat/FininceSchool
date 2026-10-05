// Static check: every identifier bound into a <Pagination> tag must be declared
// in that file. This catches a page that passes :per-page="perPage" without ever
// destructuring perPage out of usePagination - a mistake that builds cleanly and
// only shows up at runtime as a Vue warning.
import fs from 'node:fs';
import path from 'node:path';

const dir = 'resources/js/pages';
const files = fs.readdirSync(dir).filter((f) => f.endsWith('.vue'));
const failures = [];
let tagsChecked = 0;
let refsChecked = 0;

for (const file of files) {
    const source = fs.readFileSync(path.join(dir, file), 'utf8');

    // Everything declared in the <script> block, plus anything imported.
    const script = source.split('<script setup>')[1] || '';
    const declared = new Set();

    for (const m of script.matchAll(/\b(?:const|let|var|function)\s+([A-Za-z_$][\w$]*)/g)) {
        declared.add(m[1]);
    }

    // Destructured names, including multi-line and aliased forms.
    for (const m of script.matchAll(/const\s*\{([\s\S]*?)\}\s*=/g)) {
        for (const part of m[1].split(',')) {
            const name = part.split(':').pop().trim();
            if (/^[A-Za-z_$][\w$]*$/.test(name)) declared.add(name);
        }
    }

    // v-for aliases and function parameters are declared too.
    for (const m of source.matchAll(/v-for="\(?([^")]*?)\)?\s+(?:in|of)\s/g)) {
        for (const part of m[1].split(',')) {
            const name = part.trim();
            if (/^[A-Za-z_$][\w$]*$/.test(name)) declared.add(name);
        }
    }

    for (const m of script.matchAll(/function\s+[A-Za-z_$][\w$]*\s*\(([^)]*)\)/g)) {
        for (const part of m[1].split(',')) {
            const name = part.split('=')[0].trim();
            if (/^[A-Za-z_$][\w$]*$/.test(name)) declared.add(name);
        }
    }

    const tags = [...source.matchAll(/<Pagination\b[^>]*>/g)];
    if (!tags.length) continue;

    for (const tag of tags) {
        tagsChecked += 1;
        for (const attr of ['page', 'pages', 'total', 'from', 'to', 'per-page']) {
            const m = tag[0].match(new RegExp(`:${attr}="([A-Za-z_$][\\w$]*)"`));
            if (m) refsChecked += 1;
            if (m && !declared.has(m[1])) {
                failures.push(`${file}: :${attr}="${m[1]}" is not declared`);
            }
        }

        for (const ev of ['update:page', 'update:per-page']) {
            const m = tag[0].match(new RegExp(`@${ev}="([A-Za-z_$][\\w$]*)"`));
            if (m) refsChecked += 1;
            if (m && !declared.has(m[1])) {
                failures.push(`${file}: @${ev}="${m[1]}" is not declared`);
            }
        }
    }

    console.log(`  ${file}: ${tags.length} <Pagination> tag(s)`);
}

console.log(`\nchecked ${tagsChecked} tags, ${refsChecked} bound identifiers`);

if (failures.length) {
    console.log(`\n${failures.length} UNRESOLVED BINDING(S):`);
    for (const f of failures) console.log('  FAIL ' + f);
    process.exit(1);
}

console.log('\nALL BOUND IDENTIFIERS ARE DECLARED');