<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('reports.title') }}</h1>
                <p class="mt-1 text-[var(--muted)]">{{ t('reports.lead', { year: store.year }) }}</p>
            </div>
            <button class="btn btn-primary" type="button" :disabled="!report" @click="download"><i class="mdi mdi-file-pdf-box text-lg"></i>{{ t('reports.pdf') }}</button>
        </div>
        <div class="mb-4 flex gap-2 overflow-x-auto">
            <button v-for="tab in tabs" :key="tab.id" class="btn" :class="active === tab.id ? 'btn-primary' : 'btn-ghost'" type="button" @click="active = tab.id">{{ tab.label }}</button>
        </div>
        <div v-if="loading" class="text-[var(--muted)]">{{ t('reports.preparing') }}</div>
        <template v-else-if="report">
            <section v-if="active === 'income'" class="space-y-4">
                <article class="card p-5">
                    <p class="text-sm text-[var(--muted)]">{{ t('reports.totalIncome') }}</p>
                    <p class="num text-3xl font-semibold text-emerald-700">{{ afn(report.income.total) }}</p>
                </article>
                <div class="grid gap-4 lg:grid-cols-2">
                    <article class="card overflow-x-auto">
                        <table class="tbl">
                            <thead><tr><th>{{ t('reports.month') }}</th><th>{{ t('reports.income') }}</th></tr></thead>
                            <tbody>
                                <tr v-for="row in incomeMonthPageRows" :key="row.month">
                                    <td class="dari">{{ row.name }}</td>
                                    <td class="num text-emerald-700">{{ afn(row.income) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :page="incomeMonthPage" :pages="incomeMonthPages" :total="incomeMonthTotal" :from="incomeMonthFrom" :to="incomeMonthTo" @update:page="setIncomeMonthPage" :per-page="incomeMonthPerPage" @update:per-page="setIncomeMonthPerPage" />
                    </article>
                    <article class="card overflow-x-auto">
                        <table class="tbl">
                            <thead><tr><th>{{ t('reports.class') }}</th><th>{{ t('reports.payments') }}</th><th>{{ t('reports.income') }}</th></tr></thead>
                            <tbody>
                                <tr v-for="row in incomeClassPageRows" :key="row.class">
                                    <td>{{ row.class }}</td>
                                    <td>{{ row.payments }}</td>
                                    <td class="num text-emerald-700">{{ afn(row.total) }}</td>
                                </tr>
                                <tr v-if="!report.income.by_class.length"><td class="px-4 py-8 text-center text-slate-500" colspan="3">{{ t('reports.noIncome') }}</td></tr>
                            </tbody>
                        </table>
                        <Pagination :page="incomeClassPage" :pages="incomeClassPages" :total="incomeClassTotal" :from="incomeClassFrom" :to="incomeClassTo" @update:page="setIncomeClassPage" :per-page="incomeClassPerPage" @update:per-page="setIncomeClassPerPage" />
                    </article>
                </div>
            </section>

            <section v-else-if="active === 'salaries'" class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4">
                    <p class="text-sm text-[var(--muted)]">{{ t('reports.totalPaid') }} <span class="num font-bold text-rose-700">{{ afn(report.salaries.total_paid) }}</span></p>
                    <select v-model="salaryFilter" class="field w-44">
                        <option value="all">{{ t('reports.allMonths') }}</option>
                        <option value="unpaid">{{ t('status.unpaid') }}</option>
                        <option value="paid">{{ t('status.paid') }}</option>
                    </select>
                </div>
                <div class="overflow-x-auto">
                <table class="tbl min-w-[720px]">
                    <thead>
                        <tr><th>{{ t('reports.teacher') }}</th><th>{{ t('reports.month') }}</th><th>{{ t('reports.salary') }}</th><th>{{ t('common.status') }}</th><th>{{ t('reports.paidAmount') }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in salaryPageRows" :key="`${row.teacher_id}-${row.month}`">
                            <td><RouterLink class="person-link" :to="`/teachers/${row.teacher_id}`">{{ row.teacher }}</RouterLink></td>
                            <td class="dari">{{ row.month_name }}</td>
                            <td class="num">{{ afn(row.salary) }}</td>
                            <td><span class="rounded-full px-2 py-1 text-xs font-semibold ring-1" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td>
                            <td class="num text-rose-700">{{ row.status === 'paid' ? afn(row.amount_paid) : '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <Pagination :page="salaryPage" :pages="salaryPages" :total="salaryTotal" :from="salaryFrom" :to="salaryTo" @update:page="setSalaryPage" :per-page="salaryPerPage" @update:per-page="setSalaryPerPage" />
            </section>

            <section v-else-if="active === 'expenses'" class="card overflow-hidden">
                <div class="overflow-x-auto">
                <table class="tbl min-w-[760px]">
                    <thead>
                        <tr><th>{{ t('expenses.category') }}</th><th>{{ t('common.account') }}</th><th>{{ t('reports.month') }}</th><th>{{ t('expenses.description') }}</th><th>{{ t('common.amount') }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in expensePageRows" :key="row.id">
                            <td>{{ row.category }}</td>
                            <td>{{ row.account }}</td>
                            <td class="dari">{{ row.month_name }}</td>
                            <td>{{ row.description }}</td>
                            <td class="num text-rose-700">{{ afn(row.amount) }}</td>
                        </tr>
                        <tr v-if="!report.expenses.length"><td class="px-4 py-10 text-center text-slate-500" colspan="5">{{ t('reports.noExpenses') }}</td></tr>
                    </tbody>
                </table>
                </div>
                <Pagination :page="expensePage" :pages="expensePages" :total="expenseTotal" :from="expenseFrom" :to="expenseTo" @update:page="setExpensePage" :per-page="expensePerPage" @update:per-page="setExpensePerPage" />
            </section>

            <section v-else-if="active === 'monthly'" class="card overflow-x-auto">
                <table class="tbl min-w-[860px]">
                    <thead>
                        <tr>
                            <th>{{ t('reports.month') }}</th>
                            <th>{{ t('reports.income') }}</th>
                            <th>{{ t('reports.teacherSalaries') }}</th>
                            <th>{{ t('reports.otherExpenses') }}</th>
                            <th>{{ t('reports.totalExpenses') }}</th>
                            <th>{{ t('reports.balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in monthlyPageRows" :key="row.month">
                            <td class="dari font-medium">{{ row.name }}</td>
                            <td class="num text-emerald-700">{{ afn(row.income) }}</td>
                            <td class="num text-rose-700">{{ afn(row.teacher_salaries) }}</td>
                            <td class="num text-rose-700">{{ afn(row.other_expenses) }}</td>
                            <td class="num text-rose-700">{{ afn(row.expenses) }}</td>
                            <td class="num font-semibold" :class="moneyClass(row.balance)">{{ afn(row.balance) }}</td>
                        </tr>
                    </tbody>
                </table>
                <Pagination :page="monthlyPage" :pages="monthlyPages" :total="monthlyTotal" :from="monthlyFrom" :to="monthlyTo" @update:page="setMonthlyPage" :per-page="monthlyPerPage" @update:per-page="setMonthlyPerPage" />
            </section>

            <section v-else class="card overflow-hidden">
                <div class="overflow-x-auto">
                <table class="tbl min-w-[760px]">
                    <thead>
                        <tr><th>{{ t('fees.student') }}</th><th>{{ t('fees.class') }}</th><th>{{ t('reports.unpaidMonths') }}</th><th>{{ t('reports.monthlyFee') }}</th><th>{{ t('reports.totalOutstanding') }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in outstandingPageRows" :key="row.student_id">
                            <td><RouterLink class="person-link" :to="`/students/${row.student_id}`">{{ row.student }}</RouterLink></td>
                            <td>{{ row.class }}</td>
                            <td class="dari text-amber-800">{{ row.unpaid_months.map((month) => month.name).join('، ') }}</td>
                            <td class="num">{{ afn(row.monthly_fee) }}</td>
                            <td class="num font-semibold text-amber-700">{{ afn(row.total_outstanding) }}</td>
                        </tr>
                        <tr v-if="!report.outstanding.length"><td class="px-4 py-10 text-center text-slate-500" colspan="5">{{ t('reports.noOutstanding') }}</td></tr>
                    </tbody>
                </table>
                </div>
                <Pagination :page="outstandingPage" :pages="outstandingPages" :total="outstandingTotal" :from="outstandingFrom" :to="outstandingTo" @update:page="setOutstandingPage" :per-page="outstandingPerPage" @update:per-page="setOutstandingPerPage" />
            </section>
        </template>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import Pagination from '../components/Pagination.vue';
import http from '../http';
import { usePagination } from '../pagination';
import { afn, moneyClass, statusClass, statusLabel } from '../format';
import { store } from '../store';
import { t } from '../i18n';
import { printReport } from '../printReport';

const tabs = computed(() => [
    { id: 'income', label: t('reports.incomeTab') },
    { id: 'salaries', label: t('reports.salaryTab') },
    { id: 'expenses', label: t('reports.expenseTab') },
    { id: 'monthly', label: t('reports.monthlyTab') },
    { id: 'outstanding', label: t('reports.outstandingTab') },
]);
const active = ref('income');
const loading = ref(true);
const report = ref(null);
const salaryFilter = ref('unpaid');

const incomeMonthRows = computed(() => report.value?.income.monthly || []);
const incomeClassRows = computed(() => report.value?.income.by_class || []);
const monthlyRows = computed(() => report.value?.monthly || []);
const salaryRows = computed(() => {
    const rows = report.value?.salaries.rows || [];
    if (salaryFilter.value === 'all') return rows;
    return rows.filter((row) => row.status === salaryFilter.value);
});
const expenseRows = computed(() => report.value?.expenses || []);
const outstandingRows = computed(() => report.value?.outstanding || []);
const {
    page: salaryPage,
    pages: salaryPages,
    total: salaryTotal,
    from: salaryFrom,
    to: salaryTo,
    rows: salaryPageRows,
    setPage: setSalaryPage,
    perPage: salaryPerPage,
    setPerPage: setSalaryPerPage,
    reset: resetSalary,
} = usePagination(salaryRows);
const {
    page: expensePage,
    pages: expensePages,
    total: expenseTotal,
    from: expenseFrom,
    to: expenseTo,
    rows: expensePageRows,
    setPage: setExpensePage,
    perPage: expensePerPage,
    setPerPage: setExpensePerPage,
    reset: resetExpense,
} = usePagination(expenseRows);
const {
    page: outstandingPage,
    pages: outstandingPages,
    total: outstandingTotal,
    from: outstandingFrom,
    to: outstandingTo,
    rows: outstandingPageRows,
    setPage: setOutstandingPage,
    perPage: outstandingPerPage,
    setPerPage: setOutstandingPerPage,
    reset: resetOutstanding,
} = usePagination(outstandingRows);
const {
    page: incomeMonthPage,
    pages: incomeMonthPages,
    total: incomeMonthTotal,
    from: incomeMonthFrom,
    to: incomeMonthTo,
    rows: incomeMonthPageRows,
    setPage: setIncomeMonthPage,
    perPage: incomeMonthPerPage,
    setPerPage: setIncomeMonthPerPage,
    reset: resetIncomeMonth,
} = usePagination(incomeMonthRows);
const {
    page: incomeClassPage,
    pages: incomeClassPages,
    total: incomeClassTotal,
    from: incomeClassFrom,
    to: incomeClassTo,
    rows: incomeClassPageRows,
    setPage: setIncomeClassPage,
    perPage: incomeClassPerPage,
    setPerPage: setIncomeClassPerPage,
    reset: resetIncomeClass,
} = usePagination(incomeClassRows);
const {
    page: monthlyPage,
    pages: monthlyPages,
    total: monthlyTotal,
    from: monthlyFrom,
    to: monthlyTo,
    rows: monthlyPageRows,
    setPage: setMonthlyPage,
    perPage: monthlyPerPage,
    setPerPage: setMonthlyPerPage,
    reset: resetMonthly,
} = usePagination(monthlyRows);

async function load() {
    loading.value = true;
    const { data } = await http.get('/reports', { params: { year: store.year } });
    report.value = data;
    loading.value = false;
}

function download() {
    if (!report.value) return;
    printReport({
        report: report.value,
        schoolName: store.schoolName,
        today: store.today?.formatted,
    });
}

watch(salaryFilter, resetSalary);
function resetPages() {
    resetSalary();
    resetExpense();
    resetOutstanding();
    resetIncomeMonth();
    resetIncomeClass();
    resetMonthly();
}

watch(active, resetPages);
onMounted(load);
watch(() => store.year, () => {
    resetPages();
    load();
});
</script>
