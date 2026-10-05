<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('accounts.title') }}</h1>
                <p class="mt-1 text-[var(--muted)]">{{ t('accounts.lead') }}</p>
            </div>
            <button v-if="can('accounts.manage')" class="btn btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus text-lg"></i>{{ t('accounts.add') }}</button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="account in accountRows" :key="account.id" class="card fade-up flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span class="stat-icon shrink-0" :style="iconStyle(account.type)">
                            <i class="mdi" :class="iconFor(account.type)"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold tracking-tight">{{ account.name }}</h2>
                            <p class="truncate text-xs font-bold uppercase tracking-wide text-[var(--muted)]">{{ typeLabel(account.type) }}</p>
                        </div>
                    </div>
                    <RowMenu :items="rowItems(account)" />
                </div>
                <p class="num mt-4 text-2xl font-bold tracking-tight" :class="moneyClass(account.balance)">{{ afn(account.balance) }}</p>
                <dl class="mt-4 space-y-1.5 border-t border-[var(--line)] pt-3.5 text-sm">
                    <div class="flex items-center justify-between"><dt class="text-[var(--muted)]">{{ t('accounts.opening') }}</dt><dd class="num">{{ afn(account.opening_balance) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="font-semibold text-[var(--emerald-ink)]">{{ t('accounts.income') }}</dt><dd class="num font-bold text-[var(--emerald-ink)]">{{ afn(account.income) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="font-semibold text-[var(--rose-ink)]">{{ t('accounts.salaries') }}</dt><dd class="num font-bold text-[var(--rose-ink)]">{{ afn(account.teacher_salaries) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="font-semibold text-[var(--rose-ink)]">{{ t('accounts.other') }}</dt><dd class="num font-bold text-[var(--rose-ink)]">{{ afn(account.other_expenses) }}</dd></div>
                </dl>
                <p class="mt-3 text-sm text-[var(--muted)]">{{ account.details || t('common.noNotes') }}</p>
            </article>
            <article v-if="!accounts.length" class="card grid place-items-center p-10 text-center text-sm text-[var(--muted)] md:col-span-2 xl:col-span-3">
                {{ t('common.noResults') }}
            </article>
        </div>
        <Pagination class="mt-4" :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
        <Modal :open="formOpen" :title="form.id ? t('accounts.edit') : t('accounts.add')" @close="formOpen = false">
            <p v-if="formError" class="notice notice-error mb-3">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <div class="grid gap-3">
                <label><span class="label">{{ t('common.name') }}</span><input v-model="form.name" class="field"></label>
                <label>
                    <span class="label">{{ t('accounts.type') }}</span>
                    <select v-model="form.type" class="field">
                        <option value="cash">{{ t('accounts.cash') }}</option>
                        <option value="bank">{{ t('accounts.bank') }}</option>
                        <option value="other">{{ t('accounts.otherType') }}</option>
                    </select>
                </label>
                <label><span class="label">{{ t('accounts.openingBalance') }}</span><input v-model="form.opening_balance" class="field" type="number" min="0" step="0.01"></label>
                <label><span class="label">{{ t('common.details') }}</span><textarea v-model="form.details" class="field" rows="3"></textarea></label>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="formOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="saving" @click="save">{{ t('common.save') }}</button>
            </template>
        </Modal>
        <ConfirmDialog :open="confirmOpen" :title="t('accounts.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import Pagination from '../components/Pagination.vue';
import RowMenu from '../components/RowMenu.vue';
import { usePagination } from '../pagination';
import http from '../http';
import { afn, errorMessage, moneyClass } from '../format';
import { can, notify } from '../store';
import { t } from '../i18n';

const accounts = ref([]);
const { page, pages, total, from, to, rows: accountRows, setPage, perPage, setPerPage } = usePagination(accounts);
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const formError = ref('');
const form = reactive(blank());

const typeMeta = {
    cash: { icon: 'mdi-cash-multiple', color: 'var(--emerald-ink)' },
    bank: { icon: 'mdi-bank-outline', color: 'var(--primary)' },
    other: { icon: 'mdi-wallet-outline', color: 'var(--amber-ink)' },
};

function iconFor(type) {
    return typeMeta[type]?.icon || typeMeta.other.icon;
}

function iconStyle(type) {
    const color = typeMeta[type]?.color || typeMeta.other.color;
    return {
        color,
        background: `color-mix(in srgb, ${color} 13%, transparent)`,
    };
}

function rowItems(account) {
    return [
        { icon: 'mdi-pencil-outline', label: t('common.edit'), show: can('accounts.manage'), onClick: () => openEdit(account) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: can('accounts.manage'), onClick: () => askDelete(account) },
    ];
}

function blank() {
    return { id: null, name: '', type: 'cash', opening_balance: 0, details: '' };
}

function typeLabel(type) {
    if (type === 'bank') return t('accounts.bank');
    if (type === 'other') return t('accounts.otherType');
    return t('accounts.cash');
}

async function load() {
    const { data } = await http.get('/accounts');
    accounts.value = data.data;
}

function openCreate() {
    Object.assign(form, blank());
    formError.value = '';
    formOpen.value = true;
}

function openEdit(account) {
    Object.assign(form, {
        id: account.id,
        name: account.name,
        type: account.type,
        opening_balance: account.opening_balance,
        details: account.details || '',
    });
    formError.value = '';
    formOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = { ...form, opening_balance: Number(form.opening_balance) };
    delete payload.id;
    try {
        if (form.id) await http.put(`/accounts/${form.id}`, payload);
        else await http.post('/accounts', payload);
        formOpen.value = false;
        notify(t('accounts.saved'));
        await load();
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askDelete(account) {
    pending.value = account;
    confirmMessage.value = t('accounts.deleteMessage', { name: account.name });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/accounts/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('accounts.deleted'));
        await load();
    } catch (error) {
        confirmOpen.value = false;
        notify(errorMessage(error), 'error');
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>
