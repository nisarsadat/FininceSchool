<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('teachers.title') }}</h1>
                <p class="mt-1 text-[var(--muted)]">{{ t('teachers.lead') }}</p>
            </div>
            <button v-if="can('teachers.manage')" class="btn btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus text-lg"></i>{{ t('teachers.add') }}</button>
        </div>
        <div class="card mb-4 p-4">
            <input v-model="search" class="field max-w-md" :placeholder="t('teachers.search')" @input="queueLoad">
        </div>
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tbl min-w-[760px]">
                    <thead>
                        <tr>
                            <th>{{ t('salaries.teacher') }}</th>
                            <th>{{ t('students.father') }}</th>
                            <th>{{ t('teachers.joined') }}</th>
                            <th>{{ t('teachers.salary') }}</th>
                            <th>{{ t('common.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="teacher in rows" :key="teacher.id">
                            <td><PersonLink :to="`/teachers/${teacher.id}`" :name="teacher.name" /></td>
                            <td>{{ teacher.father_name }}</td>
                            <td class="dari">{{ teacher.join_hijri?.formatted }}</td>
                            <td class="num font-semibold text-[var(--rose-ink)]">{{ afn(teacher.monthly_salary) }}</td>
                            <td><span :class="statusClass(teacher.status)">{{ statusLabel(teacher.status) }}</span></td>
                            <td class="text-end">
                                <RowMenu :items="rowItems(teacher)" />
                            </td>
                        </tr>
                        <tr v-if="!teachers.length"><td class="py-10 text-center text-[var(--muted)]" colspan="6">{{ t('teachers.empty') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
        </div>

        <Modal :open="formOpen" :title="form.id ? t('teachers.edit') : t('teachers.add')" @close="formOpen = false">
            <p v-if="formError" class="notice notice-error mb-3">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <div class="grid gap-3">
                <label><span class="label">{{ t('common.name') }}</span><input v-model="form.name" class="field"></label>
                <label><span class="label">{{ t('students.fatherName') }}</span><input v-model="form.father_name" class="field"></label>
                <HijriDateField v-model="joined" :label="t('teachers.joinDate')" />
                <label><span class="label">{{ t('teachers.salaryAfn') }}</span><input v-model="form.monthly_salary" class="field" type="number" min="0" step="1"></label>
                <label>
                    <span class="label">{{ t('common.status') }}</span>
                    <select v-model="form.status" class="field">
                        <option value="active">{{ t('status.active') }}</option>
                        <option value="inactive">{{ t('status.inactive') }}</option>
                    </select>
                </label>
                <label><span class="label">{{ t('common.details') }}</span><textarea v-model="form.details" class="field" rows="3"></textarea></label>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="formOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="saving" @click="save">{{ saving ? t('common.saving') : t('common.save') }}</button>
            </template>
        </Modal>
        <ConfirmDialog :open="confirmOpen" :title="t('teachers.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import HijriDateField from '../components/HijriDateField.vue';
import Pagination from '../components/Pagination.vue';
import PersonLink from '../components/PersonLink.vue';
import RowMenu from '../components/RowMenu.vue';
import { usePagination } from '../pagination';
import http from '../http';
import { afn, errorMessage, statusClass, statusLabel } from '../format';
import { blankDate } from '../calendar';
import { can, notify, store } from '../store';
import { t } from '../i18n';

const teachers = ref([]);
const { page, pages, total, from, to, rows, setPage, reset, perPage, setPerPage } = usePagination(teachers);
const search = ref('');
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const formError = ref('');
const form = reactive(blank());
const joined = ref(blankDate(store.today));

function rowItems(teacher) {
    return [
        { to: `/teachers/${teacher.id}`, icon: 'mdi-account-outline', label: t('common.profile') },
        { to: `/payments?tab=salaries&teacher=${teacher.id}&manual=1`, icon: 'mdi-cash-minus', label: t('salaries.manual'), show: can('payments.manage') },
        { icon: 'mdi-pencil-outline', label: t('common.edit'), show: can('teachers.manage'), onClick: () => openEdit(teacher) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: can('teachers.manage'), onClick: () => askDelete(teacher) },
    ];
}
let timer;

function blank() {
    return { id: null, name: '', father_name: '', monthly_salary: '', details: '', status: 'active' };
}

function queueLoad() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        reset();
        load();
    }, 250);
}

async function load() {
    const { data } = await http.get('/teachers', { params: { search: search.value } });
    teachers.value = data.data;
}

function openCreate() {
    Object.assign(form, blank());
    joined.value = blankDate(store.today);
    formError.value = '';
    formOpen.value = true;
}

function openEdit(teacher) {
    Object.assign(form, {
        id: teacher.id,
        name: teacher.name,
        father_name: teacher.father_name,
        monthly_salary: teacher.monthly_salary,
        details: teacher.details || '',
        status: teacher.status,
    });
    joined.value = {
        year: teacher.join_hijri.year,
        month: teacher.join_hijri.month,
        day: teacher.join_hijri.day,
    };
    formError.value = '';
    formOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = {
        ...form,
        monthly_salary: Number(form.monthly_salary),
        hijri_year: joined.value.year,
        hijri_month: joined.value.month,
        hijri_day: joined.value.day,
    };
    delete payload.id;

    try {
        if (form.id) await http.put(`/teachers/${form.id}`, payload);
        else await http.post('/teachers', payload);
        formOpen.value = false;
        notify(t('teachers.saved'));
        await load();
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askDelete(teacher) {
    pending.value = teacher;
    confirmMessage.value = t('teachers.deleteMessage', { name: teacher.name });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/teachers/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('teachers.deleted'));
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
