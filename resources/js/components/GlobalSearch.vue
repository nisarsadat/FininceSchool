<template>
    <div ref="root" class="relative min-w-0 flex-1">
        <i class="mdi mdi-magnify pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-xl text-[var(--muted)]"></i>
        <input
            v-model="query"
            class="field ps-10"
            type="search"
            :placeholder="t('common.searchLead')"
            :aria-label="t('common.search')"
            @focus="open = true"
            @keydown.escape="open = false"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="openActive"
        >
        <div v-if="open && query.trim()" class="absolute inset-x-0 top-full z-40 mt-2 max-h-[28rem] overflow-y-auto rounded border border-[var(--line)] bg-[var(--surface)] py-1 shadow-2xl">
            <p v-if="loading" class="px-4 py-4 text-sm text-[var(--muted)]">{{ t('common.working') }}</p>
            <p v-else-if="!flat.length" class="px-4 py-6 text-center text-sm text-[var(--muted)]">{{ t('common.noResults') }}</p>
            <template v-else>
                <section v-for="group in groups" :key="group.id">
                    <p class="border-b border-[var(--line)] px-3 pb-1.5 pt-3 text-[10px] font-bold uppercase tracking-[.16em] text-[var(--muted)]">{{ group.label }}</p>
                    <button
                        v-for="item in group.items"
                        :key="item.key"
                        class="flex w-full items-center gap-3 px-3 py-2 text-start transition-colors hover:bg-[var(--surface-2)]"
                        :class="item.key === activeKey ? 'bg-[var(--primary-soft)] shadow-[inset_2px_0_0_var(--primary)]' : ''"
                        type="button"
                        @click="go(item)"
                    >
                        <i class="mdi text-lg text-[var(--primary)]" :class="item.icon"></i>
                        <span class="min-w-0">
                            <span class="block truncate font-bold">{{ item.title }}</span>
                            <span v-if="item.subtitle" class="block truncate text-xs text-[var(--muted)]">{{ item.subtitle }}</span>
                        </span>
                    </button>
                </section>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import http from '../http';
import { t } from '../i18n';
import { linkAllowed, navLinks } from '../nav';
import { can, store } from '../store';

const router = useRouter();
const root = ref(null);
const query = ref('');
const open = ref(false);
const loading = ref(false);
const records = ref(emptyRecords());
const activeKey = ref('');
let timer;

const pageItems = computed(() => {
    const needle = query.value.trim().toLowerCase();

    if (!needle) {
        return [];
    }

    return navLinks
        .filter((link) => !store.user || linkAllowed(link, can))
        .filter((link) => t(link.key).toLowerCase().includes(needle) || link.key.toLowerCase().includes(needle))
        .map((link) => ({
            key: `page-${link.to}`,
            title: t(link.key),
            subtitle: t('search.pages'),
            icon: link.icon,
            to: link.to,
        }));
});

const groups = computed(() => {
    const sections = [
        { id: 'pages', label: t('search.pages'), icon: 'mdi-compass-outline', items: pageItems.value },
        { id: 'students', label: t('search.students'), icon: 'mdi-account-school-outline', items: mapRecords('students', records.value.students, 'mdi-account-school-outline') },
        { id: 'teachers', label: t('search.teachers'), icon: 'mdi-human-male-board', items: mapRecords('teachers', records.value.teachers, 'mdi-human-male-board') },
        { id: 'classes', label: t('search.classes'), icon: 'mdi-google-classroom', items: mapRecords('classes', records.value.classes, 'mdi-google-classroom') },
        { id: 'expenses', label: t('search.expenses'), icon: 'mdi-receipt-text-outline', items: mapRecords('expenses', records.value.expenses, 'mdi-receipt-text-outline') },
        { id: 'accounts', label: t('search.accounts'), icon: 'mdi-bank-outline', items: mapRecords('accounts', records.value.accounts, 'mdi-bank-outline') },
        { id: 'users', label: t('search.users'), icon: 'mdi-account-key-outline', items: mapRecords('users', records.value.users, 'mdi-account-key-outline') },
    ];

    return sections.filter((section) => section.items.length);
});

const flat = computed(() => groups.value.flatMap((group) => group.items));

watch(query, (value) => {
    open.value = true;
    clearTimeout(timer);
    records.value = emptyRecords();

    if (value.trim().length < 1) {
        loading.value = false;
        return;
    }

    loading.value = true;
    timer = setTimeout(search, 220);
});

watch(flat, (items) => {
    if (!items.some((item) => item.key === activeKey.value)) {
        activeKey.value = items[0]?.key || '';
    }
});

function mapRecords(type, rows, icon) {
    return (rows || []).map((row) => ({
        key: `${type}-${row.id}`,
        title: row.title,
        subtitle: row.subtitle,
        icon,
        to: row.to,
    }));
}

function emptyRecords() {
    return { students: [], teachers: [], classes: [], expenses: [], accounts: [], users: [] };
}

async function search() {
    const q = query.value.trim();

    if (!q) {
        return;
    }

    try {
        const { data } = await http.get('/search', { params: { q } });

        if (query.value.trim() === q) {
            records.value = data;
        }
    } catch {
        records.value = emptyRecords();
    } finally {
        loading.value = false;
    }
}

function move(step) {
    const items = flat.value;
    if (!items.length) return;
    const index = items.findIndex((item) => item.key === activeKey.value);
    const next = (index + step + items.length) % items.length;
    activeKey.value = items[next].key;
}

function openActive() {
    const item = flat.value.find((entry) => entry.key === activeKey.value) || flat.value[0];
    if (item) go(item);
}

function go(item) {
    open.value = false;
    query.value = '';
    router.push(item.to);
}

function onDoc(event) {
    if (!root.value?.contains(event.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('pointerdown', onDoc));
onBeforeUnmount(() => document.removeEventListener('pointerdown', onDoc));
</script>
