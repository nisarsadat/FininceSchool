<template>
    <div>
        <div class="page-head flex flex-wrap items-center justify-between gap-3">
            <p class="text-[var(--muted)]">{{ t('salaries.lead') }}</p>
            <button v-if="can('payments.manage')" class="btn btn-danger" type="button" :disabled="!teachers.length" @click="openManual"><i class="mdi mdi-cash-minus text-lg"></i>{{ t('salaries.manual') }}</button>
        </div>
        <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="card p-4">
                <input v-model="search" class="field" :placeholder="t('salaries.search')" @input="queueSearch">
                <div class="mt-3 max-h-[640px] space-y-1 overflow-y-auto">
                    <button
                        v-for="teacher in rows"
                        :key="teacher.id"
                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-start transition-all hover:bg-[var(--surface-2)]"
                        :class="selectedId === teacher.id ? 'border-rose-500 bg-rose-500/8' : ''"
                        type="button"
                        @click="selectTeacher(teacher.id)"
                    >
                        <span class="min-w-0 flex-1">
                            <RouterLink class="person-link block truncate text-sm" :to="`/teachers/${teacher.id}`" @click.stop>{{ teacher.name }}</RouterLink>
                            <span class="num block truncate text-xs text-[var(--muted)]">{{ afn(teacher.monthly_salary) }}</span>
                        </span>
                    </button>
                </div>
                <Pagination class="mt-3" :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
            </aside>
            <section v-if="ledger" class="space-y-4">
                <div class="card grid gap-4 p-5 sm:grid-cols-3">
                    <div>
                        <p class="label mb-0.5 normal-case tracking-normal">{{ t('salaries.teacher') }}</p>
                        <RouterLink class="person-link" :to="`/teachers/${ledger.teacher.id}`">{{ ledger.teacher.name }}</RouterLink>
                    </div>
                    <div>
                        <p class="label mb-0.5 normal-case tracking-normal">{{ t('salaries.monthly') }}</p>
                        <p class="num font-bold text-[var(--rose-ink)]">{{ afn(ledger.monthly_salary) }}</p>
                    </div>
                    <div>
                        <p class="label mb-0.5 normal-case tracking-normal">{{ t('salaries.unpaid') }}</p>
                        <p class="num text-lg font-bold text-[var(--amber-ink)]">{{ afn(ledger.total_outstanding) }}</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <article v-for="month in ledger.months" :key="month.month" class="card p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-xl font-bold tracking-tight">{{ month.name }}</h2>
                            <span :class="statusClass(month.status)">{{ statusLabel(month.status) }}</span>
                        </div>
                        <p v-if="month.payment" class="mt-3 text-sm text-[var(--muted)]">{{ t('fees.paidOn', { date: month.payment.receipt_label }) }} · {{ month.payment.account?.name }}</p>
                        <p v-else class="mt-3 text-sm text-[var(--muted)]">{{ month.status === 'unpaid' ? t('salaries.unpaidMonth') : t('fees.later') }}</p>
                        <p v-if="month.payment" class="num mt-2 font-bold text-[var(--rose-ink)]">{{ afn(month.payment.amount) }}</p>
                        <div class="mt-4 flex items-center gap-2">
                            <button v-if="can('payments.manage') && !month.payment" class="btn btn-danger btn-sm" type="button" @click="openPay(month)">
                                <i class="mdi mdi-cash-minus"></i>{{ t('salaries.pay') }}
                            </button>
                            <RowMenu v-if="can('payments.manage') && month.payment" :items="monthItems(month)" />
                        </div>
                    </article>
                </div>
            </section>
            <section v-else class="card grid min-h-64 place-items-center p-8 text-sm text-[var(--muted)]">{{ t('salaries.pick') }}</section>
        </div>

        <Modal :open="payOpen" :title="manual ? t('salaries.manual') : t('salaries.payTitle')" :subtitle="modalSubtitle" @close="payOpen = false">
            <p v-if="formError" class="notice notice-error mb-3">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <div class="grid gap-3">
                <template v-if="manual">
                    <p class="notice notice-error">{{ t('salaries.manualLead') }}</p>
                    <label>
                        <span class="label">{{ t('salaries.teacher') }}</span>
                        <select v-model="selectedId" class="field" @change="onManualTeacher">
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                    </label>
                    <label>
                        <span class="label">{{ t('salaries.selectMonth') }}</span>
                        <select v-model.number="manualMonth" class="field">
                            <option v-for="month in store.months" :key="month.number" :value="month.number" :disabled="paidMonth(month.number)">{{ month.name }}{{ paidMonth(month.number) ? ` · ${t('status.paid')}` : '' }}</option>
                        </select>
                    </label>
                </template>
                <p v-else class="notice notice-error">{{ t('salaries.note') }}</p>
                <label><span class="label">{{ t('common.amount') }}</span><input v-model="amount" class="field" type="number" min="0.01" step="0.01"></label>
                <HijriDateField v-model="receipt" :label="t('fees.paymentDate')" />
                <label>
                    <span class="label">{{ t('common.account') }}</span>
                    <select v-model="accountId" class="field">
                        <option value="">{{ t('common.selectAccount') }}</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                    </select>
                </label>
                <label><span class="label">{{ t('common.notes') }}</span><textarea v-model="notes" class="field" rows="3"></textarea></label>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="payOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-danger" type="button" :disabled="saving" @click="pay">{{ saving ? t('common.saving') : t('salaries.record') }}</button>
            </template>
        </Modal>
        <ConfirmDialog :open="confirmOpen" :title="t('salaries.removeTitle')" :message="confirmMessage" :confirm-label="t('salaries.remove')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import HijriDateField from '../components/HijriDateField.vue';
import Pagination from '../components/Pagination.vue';
import RowMenu from '../components/RowMenu.vue';
import http from '../http';
import { usePagination } from '../pagination';
import { afn, errorMessage, statusClass, statusLabel } from '../format';
import { blankDate } from '../calendar';
import { can, notify, store } from '../store';
import { t } from '../i18n';

const route = useRoute();

const teachers = ref([]);
const { page, pages, total, from, to, rows, setPage, reset, perPage, setPerPage } = usePagination(teachers);
const accounts = ref([]);
const search = ref('');
const selectedId = ref(null);
const ledger = ref(null);
const payOpen = ref(false);
const manual = ref(false);
const manualMonth = ref(1);
const payMonth = ref(null);
const amount = ref('');
const accountId = ref('');
const notes = ref('');
const receipt = ref(blankDate(store.today));
const formError = ref('');
const saving = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
let timer;

function queueSearch() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        reset();
        loadTeachers();
    }, 250);
}

async function loadTeachers() {
    const { data } = await http.get('/teachers', { params: { search: search.value } });
    teachers.value = data.data;
}

async function loadAccounts() {
    const { data } = await http.get('/accounts');
    accounts.value = data.data;
    if (!accountId.value && accounts.value.length) accountId.value = accounts.value[0].id;
}

async function selectTeacher(id) {
    selectedId.value = id;
    const { data } = await http.get(`/salaries/ledger/${id}`, { params: { year: store.year } });
    ledger.value = data;
}

const modalSubtitle = computed(() => {
    if (!manual.value) return payMonth.value?.name || '';
    return store.months.find((month) => month.number === Number(manualMonth.value))?.name || '';
});

function paidMonth(number) {
    if (!ledger.value || Number(ledger.value.teacher?.id) !== Number(selectedId.value)) return false;
    return ledger.value.months.some((month) => month.month === number && month.payment);
}

function openManual() {
    manual.value = true;
    payMonth.value = null;
    const requested = Number(route.query.month || 0);
    const firstOpen = ledger.value?.months?.find((month) => !month.payment);
    manualMonth.value = requested || firstOpen?.month || store.today?.month || 1;
    amount.value = ledger.value?.monthly_salary ?? '';
    notes.value = '';
    receipt.value = blankDate(store.today);
    formError.value = '';
    payOpen.value = true;
}

async function onManualTeacher() {
    const teacher = teachers.value.find((item) => item.id === Number(selectedId.value));
    if (teacher) amount.value = teacher.monthly_salary;
    await selectTeacher(selectedId.value);
    const firstOpen = ledger.value?.months?.find((month) => !month.payment);
    if (firstOpen) manualMonth.value = firstOpen.month;
}

function monthItems(month) {
    return [{ icon: 'mdi-delete-outline', label: t('salaries.remove'), danger: true, onClick: () => askRemove(month) }];
}

function openPay(month) {
    manual.value = false;
    payMonth.value = month;
    amount.value = ledger.value.monthly_salary;
    notes.value = '';
    receipt.value = blankDate(store.today);
    formError.value = '';
    payOpen.value = true;
}

async function pay() {
    saving.value = true;
    formError.value = '';
    try {
        await http.post('/salaries', {
            teacher_id: selectedId.value,
            hijri_year: store.year,
            hijri_month: manual.value ? Number(manualMonth.value) : payMonth.value.month,
            receipt_year: receipt.value.year,
            receipt_month: receipt.value.month,
            receipt_day: receipt.value.day,
            amount: Number(amount.value),
            account_id: Number(accountId.value),
            notes: notes.value,
        });
        payOpen.value = false;
        notify(t('salaries.recorded'));
        await selectTeacher(selectedId.value);
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

function askRemove(month) {
    pending.value = month;
    confirmMessage.value = t('salaries.removeMessage', { month: month.name, name: ledger.value.teacher.name });
    confirmOpen.value = true;
}

async function remove() {
    saving.value = true;
    try {
        await http.delete(`/salaries/${pending.value.payment.id}`);
        confirmOpen.value = false;
        notify(t('salaries.removed'));
        await selectTeacher(selectedId.value);
    } catch (error) {
        confirmOpen.value = false;
        notify(errorMessage(error), 'error');
    } finally {
        saving.value = false;
    }
}

async function boot() {
    await Promise.all([loadTeachers(), loadAccounts()]);
    const requested = Number(route.query.teacher || 0);
    const initial = teachers.value.find((teacher) => teacher.id === requested) || teachers.value.find((teacher) => teacher.id === selectedId.value) || teachers.value[0];
    if (initial) await selectTeacher(initial.id);
    else ledger.value = null;
    if (route.query.manual === '1' && ledger.value) openManual();
    else if (route.query.month && ledger.value) {
        const month = ledger.value.months.find((item) => item.month === Number(route.query.month) && !item.payment);
        if (month) openPay(month);
    }
}

onMounted(boot);
watch(() => store.year, () => {
    if (selectedId.value) selectTeacher(selectedId.value);
});
watch(() => route.query.teacher, (id) => {
    const requested = Number(id || 0);
    if (requested && requested !== selectedId.value) selectTeacher(requested);
});
</script>
