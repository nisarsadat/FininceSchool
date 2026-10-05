<template>
    <nav v-if="total > 0" class="pager" :aria-label="t('common.pagination')">
        <div class="pager-meta">
            <p class="pager-summary">{{ t('common.showing', { from, to, total }) }}</p>
            <div v-if="perPage" ref="sizeRoot" class="pager-size" @keydown="onSizeKeydown">
                <span :id="sizeLabelId" class="pager-size-label">{{ t('common.perPage') }}</span>
                <button
                    class="pager-trigger"
                    type="button"
                    :aria-labelledby="sizeLabelId"
                    aria-haspopup="listbox"
                    :aria-expanded="sizeOpen"
                    @click="toggleSize"
                >
                    <span class="pager-trigger-value num">{{ perPage }}</span>
                    <i class="mdi mdi-chevron-down pager-caret" :class="{ 'pager-caret-open': sizeOpen }" aria-hidden="true"></i>
                </button>

                <Transition name="pager-pop">
                    <ul
                        v-if="sizeOpen"
                        class="pager-menu"
                        role="listbox"
                        :aria-labelledby="sizeLabelId"
                        :aria-activedescendant="activeId"
                    >
                        <li
                            v-for="(option, index) in perPageOptions"
                            :id="`${menuId}-${option}`"
                            :key="option"
                            class="pager-option"
                            :class="{ 'pager-option-active': option === perPage, 'pager-option-focus': index === focusIndex }"
                            role="option"
                            :aria-selected="option === perPage"
                            @mouseenter="focusIndex = index"
                            @click="choose(option)"
                        >
                            <span class="num">{{ option }}</span>
                            <i v-if="option === perPage" class="mdi mdi-check text-sm" aria-hidden="true"></i>
                        </li>
                    </ul>
                </Transition>
            </div>
        </div>

        <div v-if="pages > 1" class="pager-controls">
            <button
                class="pager-arrow"
                type="button"
                :disabled="page <= 1"
                :aria-label="t('common.prev')"
                @click="go(page - 1)"
            >
                <i class="mdi text-base" :class="rtl ? 'mdi-chevron-right' : 'mdi-chevron-left'"></i>
            </button>

            <div class="pager-track">
                <button
                    v-for="item in items"
                    :key="item.key"
                    class="pager-num"
                    :class="{ 'pager-active': item.page === page, 'pager-gap': item.page === null }"
                    type="button"
                    :disabled="item.page === null || item.page === page"
                    :aria-current="item.page === page ? 'page' : undefined"
                    @click="item.page && go(item.page)"
                >
                    {{ item.label }}
                </button>
            </div>

            <button
                class="pager-arrow"
                type="button"
                :disabled="page >= pages"
                :aria-label="t('common.next')"
                @click="go(page + 1)"
            >
                <i class="mdi text-base" :class="rtl ? 'mdi-chevron-left' : 'mdi-chevron-right'"></i>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { t } from '../i18n';
import { perPageOptions } from '../pagination';
import { store } from '../store';

const props = defineProps({
    page: { type: Number, required: true },
    pages: { type: Number, required: true },
    total: { type: Number, required: true },
    from: { type: Number, required: true },
    to: { type: Number, required: true },
    perPage: { type: Number, default: 0 },
});

const emit = defineEmits(['update:page', 'update:perPage']);

const rtl = computed(() => store.locale === 'fa' || store.locale === 'ps');

function go(next) {
    const target = Math.min(props.pages, Math.max(1, next));

    if (target !== props.page) {
        emit('update:page', target);
    }
}

/* --- Page size menu -------------------------------------------------------
   A native <select> renders its popup with OS chrome, which ignores the theme
   entirely: the list came out in bright system blue against the dark surface
   and looked nothing like the rest of the interface. This is a real listbox
   instead, so it is styled, fully keyboard operable, and mirrored in RTL.
*/
const sizeRoot = ref(null);
const sizeOpen = ref(false);
const focusIndex = ref(0);
const menuId = `pager-size-${Math.random().toString(36).slice(2, 8)}`;
const sizeLabelId = `${menuId}-label`;
const activeId = computed(() => (sizeOpen.value ? `${menuId}-${perPageOptions[focusIndex.value]}` : undefined));

function openSize(where = 'current') {
    const index = perPageOptions.indexOf(props.perPage);
    focusIndex.value = index === -1 ? 0 : index;

    if (where === 'first') focusIndex.value = 0;
    if (where === 'last') focusIndex.value = perPageOptions.length - 1;

    sizeOpen.value = true;
    // Only listen while the menu is actually open, so a page holding six pagers
    // does not leave six idle document listeners behind.
    document.addEventListener('pointerdown', onSizePointerdown);
}

function closeSize() {
    sizeOpen.value = false;
    document.removeEventListener('pointerdown', onSizePointerdown);
}

function toggleSize() {
    if (sizeOpen.value) {
        closeSize();

        return;
    }

    openSize();
}

function moveFocus(step) {
    const count = perPageOptions.length;
    focusIndex.value = (focusIndex.value + step + count) % count;
}

function choose(option) {
    closeSize();
    emit('update:perPage', Number(option));
}

// Keep the highlighted row on the current value when the size changes elsewhere.
watch(() => props.perPage, () => {
    const index = perPageOptions.indexOf(props.perPage);

    if (index !== -1) focusIndex.value = index;
});

function onSizeKeydown(event) {
    const { key } = event;

    if (!sizeOpen.value) {
        // From the trigger, the arrow keys open the menu on the first or last
        // row, which is what a native select does.
        if (key === 'ArrowDown' || key === 'ArrowUp') {
            event.preventDefault();
            openSize(key === 'ArrowDown' ? 'first' : 'last');
        }

        return;
    }

    if (key === 'Escape') {
        event.preventDefault();
        closeSize();

        return;
    }

    if (key === 'ArrowDown' || key === 'ArrowUp') {
        event.preventDefault();
        moveFocus(key === 'ArrowDown' ? 1 : -1);

        return;
    }

    if (key === 'Home' || key === 'End') {
        event.preventDefault();
        focusIndex.value = key === 'Home' ? 0 : perPageOptions.length - 1;

        return;
    }

    if (key === 'Enter' || key === ' ') {
        event.preventDefault();
        choose(perPageOptions[focusIndex.value]);

        return;
    }

    if (key === 'Tab') {
        closeSize();
    }
}

function onSizePointerdown(event) {
    if (!sizeRoot.value?.contains(event.target)) {
        closeSize();
    }
}

// Covers the case where the list is torn down while the menu is still open.
onBeforeUnmount(() => document.removeEventListener('pointerdown', onSizePointerdown));

const items = computed(() => {
    const last = props.pages;
    const current = props.page;
    const list = [];
    const push = (page) => list.push({ key: `p-${page}`, page, label: String(page) });
    const gap = (key) => list.push({ key, page: null, label: '…' });

    // First and last are always reachable, plus the current page and its two
    // neighbours, capped at seven slots so the pill keeps a stable width
    // instead of reflowing as the user moves through it. Near either end the
    // window stretches into a contiguous run, which avoids a pointless "1 2 … 7".
    const window = new Set([1, last, current]);

    if (current - 1 > 1) window.add(current - 1);
    if (current + 1 < last) window.add(current + 1);

    if (current <= 4) {
        for (let page = 2; page <= Math.min(4, last); page += 1) window.add(page);
    }

    if (current >= last - 3) {
        for (let page = Math.max(1, last - 3); page < last; page += 1) window.add(page);
    }

    const numbers = [...window].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);
    let previous = 0;

    for (const number of numbers) {
        if (previous && number - previous > 1) {
            gap(`gap-${previous}-${number}`);
        }

        push(number);
        previous = number;
    }

    return list;
});
</script>

<style scoped>
.pager {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 0.75rem;
    border-top: 1px solid var(--line);
    padding: 0.6rem 0.85rem;
}

.pager-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.pager-summary {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--muted);
}

/* "Per page" control, sized to match the pager so the row reads as one band. */
.pager-size {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.pager-size-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--muted);
    white-space: nowrap;
}

.pager-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    height: 1.7rem;
    padding-inline: 0.55rem 0.45rem;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: transparent;
    color: var(--ink);
    cursor: pointer;
    transition: border-color .14s ease, background-color .14s ease;
}

.pager-trigger:hover,
.pager-trigger[aria-expanded='true'] {
    background: var(--surface-2);
    border-color: var(--muted);
}

.pager-trigger-value {
    font-size: 0.72rem;
    font-weight: 700;
    min-width: 1.4rem;
    text-align: center;
}

.pager-caret {
    font-size: 0.85rem;
    color: var(--muted);
    transition: transform .16s ease;
}

.pager-caret-open {
    transform: rotate(180deg);
}

/* The menu: same surface, border and radius language as every other popover. */
.pager-menu {
    position: absolute;
    inset-inline-start: 0;
    bottom: calc(100% + 0.4rem);
    z-index: 30;
    min-width: 4.75rem;
    margin: 0;
    padding: 0.2rem;
    list-style: none;
    border-radius: 4px;
    border: 1px solid var(--line);
    background: var(--surface);
    box-shadow: 0 8px 24px -8px rgb(0 0 0 / 0.35);
}

.pager-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.3rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--muted);
    cursor: pointer;
    transition: background-color .12s ease, color .12s ease;
}

.pager-option-focus {
    background: var(--surface-2);
    color: var(--ink);
}

.pager-option-active {
    color: var(--primary);
    font-weight: 700;
}

.pager-option-active.pager-option-focus {
    color: var(--primary);
}

.pager-pop-enter-active,
.pager-pop-leave-active {
    transition: opacity .12s ease, transform .12s ease;
}

.pager-pop-enter-from,
.pager-pop-leave-to {
    opacity: 0;
    transform: translateY(4px);
}

.pager-controls {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

/* Round arrow buttons sit outside the track, as in the reference. */
.pager-arrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    flex: none;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: transparent;
    color: var(--muted);
    transition: color .14s ease, background-color .14s ease, border-color .14s ease;
}

.pager-arrow:hover:not(:disabled) {
    background: var(--surface-2);
    border-color: var(--muted);
    color: var(--ink);
}

.pager-arrow:disabled {
    opacity: 0.35;
}

/* The pill that holds the numbers. */
.pager-track {
    display: inline-flex;
    align-items: center;
    gap: 0.05rem;
    padding: 0.15rem;
    border-radius: 999px;
    background: var(--surface-2);
}

.pager-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.7rem;
    height: 1.7rem;
    padding: 0 0.3rem;
    border: 0;
    border-radius: 999px;
    background: transparent;
    font-size: 0.76rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--muted);
    transition: color .14s ease, background-color .14s ease;
}

.pager-num:hover:not(:disabled):not(.pager-active) {
    background: var(--surface);
    color: var(--ink);
}

/* The active page is a tall chip that breaks out of the track, like the
   reference. Sizing is fixed so the pill never changes width between pages. */
.pager-active {
    min-width: 1.7rem;
    height: 2rem;
    margin: 0 -0.1rem;
    background: var(--primary);
    color: #fff;
    font-weight: 700;
}

.pager-gap {
    min-width: 1.2rem;
    padding: 0;
    cursor: default;
}

.pager-gap:disabled {
    opacity: 0.55;
}

@media (max-width: 640px) {
    .pager {
        justify-content: center;
    }

    .pager-meta {
        justify-content: center;
    }
}
</style>
