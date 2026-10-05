<template>
    <div>
        <div class="mb-4 flex justify-end">
            <button v-if="can('expenses.manage')" class="btn btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus text-lg"></i>{{ t('expenses.add') }}</button>
        </div>
        <div class="card mb-4 grid gap-3 p-4 md:grid-cols-4">
            <input v-model="filters.search" class="field" :placeholder="t('expenses.search')" @input="queueLoad">
            <select v-model="filters.month" class="field" @change="reload">
                <option value="">{{ t('expenses.allMonths') }}</option>
                <option v-for="month in store.months" :key="month.number" :value="month.number">{{ month.name }}</option>
            </select>
            <select v-model="filters.expense_category_id" class="field" @change="reload">
                <option value="">{{ t('expenses.allCategories') }}</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
            <select v-model="filters.account_id" class="field" @change="reload">
                <option value="">{{ t('expenses.allAccounts') }}</option>
                <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</option>
            </select>
        </div>
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="tbl min-w-[760px]">
                <thead>
                    <tr>
                        <th>{{ t('expenses.date') }}</th>
                        <th>{{ t('expenses.category') }}</th>
                        <th>{{ t('common.account') }}</th>
                        <th>{{ t('expenses.description') }}</th>
                        <th>{{ t('common.amount') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="expense in rows" :key="expense.id">
                        <td class="dari">{{ expense.hijri_label }}</td>
                        <td>{{ expense.category?.name }}</td>
                        <td>{{ expense.account?.name }}</td>
                        <td>{{ expense.description }}</td>
                        <td class="num font-semibold text-rose-700">{{ afn(expense.amount) }}</td>
                        <td class="text-end">
                            <RowMenu :items="rowItems(expense)" />
                        </td>
                    </tr>
                    <tr v-if="!expenses.length"><td class="px-4 py-10 text-center text-slate-500" colspan="6">{{ t('expenses.empty') }}</td></tr>
                </tbody>
            </table>
            </div>
            <Pagination :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
        </div>

        <Modal :open="formOpen" :title="form.id ? t('expenses.edit') : t('expenses.add')" @close="formOpen = false">
            <p v-if="formError" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ formError }}</p>
            <div class="grid gap-3">
                <label>
                    <span class="label">{{ t('expenses.category') }}</span>
                    <select v-model="form.expense_category_id" class="field">
                        <option value="">{{ t('expenses.allCategories') }}</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
                <label>
                    <span class="label">{{ t('common.account') }}</span>
                    <select v-model="form.account_id" class="field">
                        <option value="">{{ t('common.selectAccount') }}</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                    </select>
                </label>
                <label><span class="label">{{ t('common.amount') }}</span><input v-model="form.amount" class="field" type="number" min="0.01" step="0.01"></label>
                <HijriDateField v-model="spent" :label="t('common.afghanDate')" />
                <label><span class="label">{{ t('expenses.description') }}</span><textarea v-model="form.description" class="field" rows="3"></textarea></label>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="formOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="saving" @click="save">{{ saving ? t('common.saving') : t('common.save') }}</button>
            </template>
        </Modal>
        <ConfirmDialog :open="confirmOpen" :title="t('expenses.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import HijriDateField from '../components/HijriDateField.vue';
import Pagination from '../components/Pagination.vue';
import RowMenu from '../components/RowMenu.vue';
import { usePagination } from '../pagination';
import http from '../http';
import { afn, errorMessage } from '../format';
import { blankDate } from '../calendar';
import { can, notify, store } from '../store';
import { t } from '../i18n';

const expenses = ref([]);
const { page, pages, total, from, to, rows, setPage, reset, perPage, setPerPage } = usePagination(expenses);
const categories = ref([]);
const accounts = ref([]);
const filters = reactive({ search: '', month: '', expense_category_id: '', account_id: '' });
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const formError = ref('');
const form = reactive(blank());

function rowItems(expense) {
    return [
        { icon: 'mdi-pencil-outline', label: t('common.edit'), show: can('expenses.manage'), onClick: () => openEdit(expense) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: can('expenses.manage'), onClick: () => askDelete(expense) },
    ];
}
const spent = ref(blankDate(store.today));
let timer;

function blank() {
    return { id: null, expense_category_id: '', account_id: '', amount: '', description: '' };
}

function queueLoad() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        reset();
        load();
    }, 250);
}

async function loadLookups() {
    const [categoryResponse, accountResponse] = await Promise.all([http.get('/expense-categories'), http.get('/accounts')]);
    categories.value = categoryResponse.data.data;
    accounts.value = accountResponse.data.data;
}

function reload() {
    reset();
    load();
}

async function load() {
    const { data } = await http.get('/expenses', { params: { ...filters, year: store.year } });
    expenses.value = data.data;
}

function openCreate() {
    Object.assign(form, blank());
    spent.value = blankDate(store.today);
    formError.value = '';
    formOpen.value = true;
}

function openEdit(expense) {
    Object.assign(form, {
        id: expense.id,
        expense_category_id: expense.expense_category_id,
        account_id: expense.account_id,
        amount: expense.amount,
        description: expense.description,
    });
    spent.value = { year: expense.hijri_year, month: expense.hijri_month, day: expense.hijri_day };
    formError.value = '';
    formOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = {
        expense_category_id: Number(form.expense_category_id),
        account_id: Number(form.account_id),
        amount: Number(form.amount),
        description: form.description,
        hijri_year: spent.value.year,
        hijri_month: spent.value.month,
        hijri_day: spent.value.day,
    };
    try {
        if (form.id) await http.put(`/expenses/${form.id}`, payload);
        else await http.post('/expenses', payload);
        formOpen.value = false;
        notify(t('expenses.saved'));
        await load();
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askDelete(expense) {
    pending.value = expense;
    confirmMessage.value = t('expenses.deleteMessage', { name: expense.category?.name || t('expenses.title'), amount: afn(expense.amount) });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/expenses/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('expenses.deleted'));
        await load();
    } catch (error) {
        confirmOpen.value = false;
        notify(errorMessage(error), 'error');
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadLookups();
    await load();
});
watch(() => store.year, reload);
</script>
