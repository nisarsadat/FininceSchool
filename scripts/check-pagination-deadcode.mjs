// Scans Pagination.vue for two things a compiler will not tell you about:
// CSS classes that are defined but never used, and script identifiers that are
// declared but never referenced. Vue <Transition> classes are exempt, because
// the framework applies them at runtime by name rather than from the template.
import fs from 'node:fs';

const path = 'resources/js/components/Pagination.vue';
const source = fs.readFileSync(path, 'utf8');
const template = source.split('</template>')[0];
const script = source.split('<script setup>')[1].split('</script>')[0];
const style = source.split('<style scoped>')[1].split('</style>')[0];

let failures = 0;

// --- unused CSS -------------------------------------------------------------
const transitionPrefixes = ['pager-pop-enter', 'pager-pop-leave'];
const classes = [...new Set([...style.matchAll(/\.([a-z][\w-]*)/g)].map((m) => m[1]))];
const deadClasses = classes.filter(
    (name) => !template.includes(name) && !transitionPrefixes.some((p) => name.startsWith(p)),
);

console.log(`CSS classes defined: ${classes.length}`);
if (deadClasses.length) {
    failures += 1;
    console.log(`  UNUSED CSS: ${deadClasses.join(', ')}`);
} else {
    console.log('  no unused CSS classes');
}

// --- unused identifiers -----------------------------------------------------
const declared = [
    ...new Set([
        ...[...script.matchAll(/\bfunction\s+([A-Za-z_$][\w$]*)/g)].map((m) => m[1]),
        ...[...script.matchAll(/\bconst\s+([A-Za-z_$][\w$]*)\s*=/g)].map((m) => m[1]),
    ]),
];

// Local bindings inside function bodies are declared more than once by design;
// only top-level declarations are interesting here.
const topLevel = declared.filter((name) => {
    const definition = new RegExp(`^(?:function|const)\\s+${name}\\b`, 'm');

    return definition.test(script);
});

const unused = topLevel.filter((name) => {
    const hits = source.match(new RegExp(`\\b${name}\\b`, 'g')) || [];

    return hits.length < 2;
});

console.log(`top-level identifiers declared: ${topLevel.length}`);
if (unused.length) {
    failures += 1;
    console.log(`  UNUSED IDENTIFIERS: ${unused.join(', ')}`);
} else {
    console.log('  no unused identifiers');
}

// --- required accessibility wiring -----------------------------------------
const needs = [
    ['role="listbox"', 'the menu must be a listbox'],
    ['role="option"', 'each row must be an option'],
    [':aria-selected=', 'options must expose their selected state'],
    [':aria-expanded=', 'the trigger must expose its expanded state'],
    [':aria-activedescendant=', 'keyboard focus must be tracked'],
    ['aria-haspopup="listbox"', 'the trigger must advertise the popup'],
];
const missing = needs.filter(([needle]) => !source.includes(needle)).map(([, why]) => why);

if (missing.length) {
    failures += 1;
    console.log(`  MISSING A11Y: ${missing.join('; ')}`);
} else {
    console.log('  listbox accessibility wiring present');
}

console.log(failures === 0 ? '\nALL PASS' : `\n${failures} PROBLEM(S)`);
process.exit(failures === 0 ? 0 : 1);