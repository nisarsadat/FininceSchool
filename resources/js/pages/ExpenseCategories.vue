<template>
    <div>
        <div class="page-head flex flex-wrap items-center justify-between gap-3">
            <p class="text-[var(--muted)]">{{ t('categories.lead') }}</p>
            <button v-if="can('expenses.manage')" class="btn btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus text-lg"></i>{{ t('categories.add') }}</button>
        </div>
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tbl min-w-[480px]">
                    <thead>
                        <tr>
                            <th>{{ t('expenses.category') }}</th>
                            <th>{{ t('categories.count') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="category in categoryRows" :key="category.id">
                            <td>
                                <span class="inline-flex items-center gap-2.5 font-semibold">
                                    <i class="mdi mdi-tag-outline text-lg text-[var(--primary-ink)]"></i>{{ category.name }}
                                </span>
                            </td>
                            <td class="num text-[var(--muted)]">{{ category.expenses_count }}</td>
                            <td class="text-end">
                                <RowMenu :items="rowItems(category)" />
                            </td>
                        </tr>
                        <tr v-if="!categories.length">
                            <td class="py-10 text-center text-[var(--muted)]" colspan="3">{{ t('common.noResults') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
        </div>
        <Modal :open="formOpen" :title="form.id ? t('categories.edit') : t('categories.add')" @close="formOpen = false">
            <p v-if="formError" class="notice notice-error mb-3">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <label><span class="label">{{ t('common.name') }}</span><input v-model="form.name" class="field"></label>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="formOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="saving" @click="save">{{ t('common.save') }}</button>
            </template>
        </Modal>
        <ConfirmDialog :open="confirmOpen" :title="t('categories.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import Pagination from '../components/Pagination.vue';
import RowMenu from '../components/RowMenu.vue';
import { usePagination } from '../pagination';
import http from '../http';
import { errorMessage } from '../format';
import { can, notify } from '../store';
import { t } from '../i18n';

const categories = ref([]);
const { page, pages, total, from, to, rows: categoryRows, setPage, perPage, setPerPage } = usePagination(categories);
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const formError = ref('');
const form = reactive({ id: null, name: '' });

function rowItems(category) {
    return [
        { icon: 'mdi-pencil-outline', label: t('common.edit'), show: can('expenses.manage'), onClick: () => openEdit(category) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: can('expenses.manage'), onClick: () => askDelete(category) },
    ];
}

async function load() {
    const { data } = await http.get('/expense-categories');
    categories.value = data.data;
}

function openCreate() {
    form.id = null;
    form.name = '';
    formError.value = '';
    formOpen.value = true;
}

function openEdit(category) {
    form.id = category.id;
    form.name = category.name;
    formError.value = '';
    formOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    try {
        if (form.id) await http.put(`/expense-categories/${form.id}`, { name: form.name });
        else await http.post('/expense-categories', { name: form.name });
        formOpen.value = false;
        notify(t('categories.saved'));
        await load();
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askDelete(category) {
    pending.value = category;
    confirmMessage.value = t('categories.deleteMessage', { name: category.name });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/expense-categories/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('categories.deleted'));
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
