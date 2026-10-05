<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('students.title') }}</h1>
                <p class="mt-1 text-[var(--muted)]">{{ t('students.lead', { year: store.year }) }}</p>
            </div>
            <button v-if="can('students.manage')" class="btn btn-primary" type="button" @click="openCreate"><i class="mdi mdi-plus text-lg"></i>{{ t('students.add') }}</button>
        </div>

        <div class="card mb-4 grid gap-3 p-4 md:grid-cols-4">
            <input v-model="filters.search" class="field md:col-span-2" :placeholder="t('students.search')" @input="queueLoad">
            <select v-model="filters.class_id" class="field" @change="reload">
                <option value="">{{ t('students.allClasses') }}</option>
                <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }}</option>
            </select>
            <select v-model="filters.status" class="field" @change="reload">
                <option value="">{{ t('students.allStatuses') }}</option>
                <option value="active">{{ t('status.active') }}</option>
                <option value="inactive">{{ t('status.inactive') }}</option>
            </select>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tbl min-w-[880px]">
                    <thead>
                        <tr>
                            <th>{{ t('fees.student') }}</th>
                            <th>{{ t('students.father') }}</th>
                            <th>{{ t('students.grandfather') }}</th>
                            <th>{{ t('students.idCard') }}</th>
                            <th>{{ t('students.class') }}</th>
                            <th>{{ t('students.fee') }}</th>
                            <th>{{ t('students.outstanding') }}</th>
                            <th>{{ t('common.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in rows" :key="student.id">
                            <td><PersonLink :to="`/students/${student.id}`" :name="student.name" /></td>
                            <td>{{ student.father_name }}</td>
                            <td>{{ student.grandfather_name || '—' }}</td>
                            <td class="num">{{ student.id_card_number || '—' }}</td>
                            <td>{{ student.school_class?.name }}</td>
                            <td class="num text-[var(--primary-ink)]">{{ afn(student.monthly_fee) }}</td>
                            <td class="num font-bold text-[var(--amber-ink)]">{{ afn(student.outstanding) }}</td>
                            <td><span :class="statusClass(student.status)">{{ statusLabel(student.status) }}</span></td>
                            <td class="text-end">
                                <RowMenu :items="rowItems(student)" />
                            </td>
                        </tr>
                        <tr v-if="!students.length">
                            <td class="py-10 text-center text-[var(--muted)]" colspan="9">{{ t('students.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
        </div>

        <Modal :open="formOpen" :title="form.id ? t('students.edit') : t('students.add')" @close="formOpen = false">
            <p v-if="formError" class="notice notice-error mb-3">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <div class="grid gap-3">
                <label><span class="label">{{ t('common.name') }}</span><input v-model="form.name" class="field"></label>
                <label><span class="label">{{ t('students.fatherName') }}</span><input v-model="form.father_name" class="field"></label>
                <label><span class="label">{{ t('students.grandfatherName') }}</span><input v-model="form.grandfather_name" class="field"></label>
                <label><span class="label">{{ t('students.idNumber') }}</span><input v-model="form.id_card_number" class="field"></label>
                <label>
                    <span class="label">{{ t('students.class') }}</span>
                    <select v-model="form.class_id" class="field">
                        <option value="">{{ t('students.selectClass') }}</option>
                        <option v-for="item in classes" :key="item.id" :value="item.id">{{ item.name }} · {{ afn(item.monthly_fee) }}</option>
                    </select>
                    <p v-if="selectedFee !== null" class="mt-1 text-xs font-semibold text-[var(--primary-ink)]">{{ t('students.feeFromClass', { amount: afn(selectedFee) }) }}</p>
                </label>
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

        <ConfirmDialog :open="confirmOpen" :title="t('students.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import Pagination from '../components/Pagination.vue';
import PersonLink from '../components/PersonLink.vue';
import RowMenu from '../components/RowMenu.vue';
import { usePagination } from '../pagination';
import http from '../http';
import { afn, errorMessage, statusClass, statusLabel } from '../format';
import { can, notify, store } from '../store';
import { t } from '../i18n';

const students = ref([]);
const { page, pages, total, from, to, rows, setPage, reset, perPage, setPerPage } = usePagination(students);
const classes = ref([]);
const filters = reactive({ search: '', class_id: '', status: '' });
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const formError = ref('');
const form = reactive(blank());
let timer;

function rowItems(student) {
    return [
        { to: `/students/${student.id}`, icon: 'mdi-account-outline', label: t('common.profile') },
        { to: `/payments?tab=fees&student=${student.id}&manual=1`, icon: 'mdi-cash-plus', label: t('fees.manual'), show: can('payments.manage') },
        { icon: 'mdi-pencil-outline', label: t('common.edit'), show: can('students.manage'), onClick: () => openEdit(student) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: can('students.manage'), onClick: () => askDelete(student) },
    ];
}

const selectedFee = computed(() => {
    const match = classes.value.find((item) => item.id === Number(form.class_id));
    return match ? match.monthly_fee : null;
});

function blank() {
    return { id: null, name: '', father_name: '', grandfather_name: '', id_card_number: '', class_id: '', details: '', status: 'active' };
}

function queueLoad() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        reset();
        load();
    }, 250);
}

async function loadClasses() {
    const { data } = await http.get('/classes');
    classes.value = data.data;
}

function reload() {
    reset();
    load();
}

async function load() {
    const { data } = await http.get('/students', {
        params: { search: filters.search, class_id: filters.class_id, status: filters.status, year: store.year },
    });
    students.value = data.data;
}

function openCreate() {
    Object.assign(form, blank());
    formError.value = '';
    formOpen.value = true;
}

function openEdit(student) {
    Object.assign(form, {
        id: student.id,
        name: student.name,
        father_name: student.father_name,
        grandfather_name: student.grandfather_name || '',
        id_card_number: student.id_card_number || '',
        class_id: student.class_id,
        details: student.details || '',
        status: student.status,
    });
    formError.value = '';
    formOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = { ...form, class_id: Number(form.class_id) };
    delete payload.id;

    try {
        if (form.id) {
            await http.put(`/students/${form.id}`, payload);
        } else {
            await http.post('/students', payload);
        }
        formOpen.value = false;
        notify(t('students.saved'));
        await load();
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askDelete(student) {
    pending.value = student;
    confirmMessage.value = t('students.deleteMessage', { name: student.name });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/students/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('students.deleted'));
        await load();
    } catch (error) {
        confirmOpen.value = false;
        notify(errorMessage(error), 'error');
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    await loadClasses();
    await load();
});
watch(() => store.year, reload);
</script>
