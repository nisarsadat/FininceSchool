<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('classes.title') }}</h1>
                <p class="mt-1 text-[var(--muted)]">{{ t('classes.lead') }}</p>
            </div>
            <button v-if="can('classes.manage')" class="btn btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus text-lg"></i>{{ t('classes.add') }}</button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="item in classRows" :key="item.id" class="card fade-up flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span class="stat-icon shrink-0" style="color: var(--primary-ink); background: var(--primary-soft); border: 1px solid color-mix(in srgb, var(--primary) 28%, transparent);">
                            <i class="mdi mdi-google-classroom"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold tracking-tight">{{ item.name }}</h2>
                            <p class="truncate text-xs text-[var(--muted)]">
                                <i class="mdi mdi-map-marker-outline me-1"></i>{{ item.location || t('classes.noLocation') }}
                            </p>
                        </div>
                    </div>
                    <RowMenu :items="rowItems(item)" />
                </div>
                <p class="mt-3 text-sm text-[var(--muted)]">{{ item.details || t('classes.noDetails') }}</p>
                <div class="mt-4 flex items-center justify-between border-t border-[var(--line)] pt-3.5">
                    <p class="text-xs font-bold text-[var(--muted)]">{{ t('classes.count', { count: item.students_count }) }}</p>
                    <p class="num text-sm font-bold text-[var(--primary-ink)]">{{ afn(item.monthly_fee) }}</p>
                </div>
            </article>
            <article v-if="!classes.length" class="card grid place-items-center p-10 text-center text-sm text-[var(--muted)] md:col-span-2 xl:col-span-3">
                {{ t('common.noResults') }}
            </article>
        </div>
        <Pagination class="mt-4" :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />

        <Modal :open="formOpen" :title="form.id ? t('classes.edit') : t('classes.add')" @close="formOpen = false">
            <p v-if="formError" class="notice notice-error mb-3">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <div class="grid gap-3">
                <label><span class="label">{{ t('common.name') }}</span><input v-model="form.name" class="field"></label>
                <label><span class="label">{{ t('classes.location') }}</span><input v-model="form.location" class="field"></label>
                <label><span class="label">{{ t('classes.monthlyFee') }}</span><input v-model="form.monthly_fee" class="field" type="number" min="0" step="1"></label>
                <label><span class="label">{{ t('common.details') }}</span><textarea v-model="form.details" class="field" rows="3"></textarea></label>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="formOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="saving" @click="save">{{ saving ? t('common.saving') : t('common.save') }}</button>
            </template>
        </Modal>
        <ConfirmDialog :open="confirmOpen" :title="t('classes.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
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
import { afn, errorMessage } from '../format';
import { can, notify } from '../store';
import { t } from '../i18n';

const classes = ref([]);
const { page, pages, total, from, to, rows: classRows, setPage, perPage, setPerPage } = usePagination(classes);
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const formError = ref('');
const form = reactive(blank());

function rowItems(item) {
    return [
        { icon: 'mdi-pencil-outline', label: t('common.edit'), show: can('classes.manage'), onClick: () => openEdit(item) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: can('classes.manage'), onClick: () => askDelete(item) },
    ];
}

function blank() {
    return { id: null, name: '', location: '', monthly_fee: '', details: '' };
}

async function load() {
    const { data } = await http.get('/classes');
    classes.value = data.data;
}

function openCreate() {
    Object.assign(form, blank());
    formError.value = '';
    formOpen.value = true;
}

function openEdit(item) {
    Object.assign(form, { id: item.id, name: item.name, location: item.location || '', monthly_fee: item.monthly_fee, details: item.details || '' });
    formError.value = '';
    formOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = { ...form, monthly_fee: Number(form.monthly_fee) };
    delete payload.id;
    try {
        if (form.id) await http.put(`/classes/${form.id}`, payload);
        else await http.post('/classes', payload);
        formOpen.value = false;
        notify(t('classes.saved'));
        await load();
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askDelete(item) {
    pending.value = item;
    confirmMessage.value = t('classes.deleteMessage', { name: item.name });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/classes/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('classes.deleted'));
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
