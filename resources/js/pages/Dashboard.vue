<template>
    <AppLayout>
        <div class="page-head">
            <h1 class="page-title">{{ t('dashboard.title') }}</h1>
            <p class="mt-1 text-[var(--muted)]">{{ t('dashboard.lead') }}</p>
        </div>

        <div v-if="loading" class="text-[var(--muted)]">{{ t('dashboard.loading') }}</div>
        <template v-else-if="data">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="stat in stats" :key="stat.label" class="card fade-up flex items-center gap-4 p-4">
                    <span class="stat-icon shrink-0" :style="stat.iconStyle">
                        <i class="mdi" :class="stat.icon"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-bold uppercase tracking-[.1em] text-[var(--muted)]">{{ stat.label }}</p>
                        <p class="num mt-1.5 truncate text-[21px] font-bold leading-tight" :style="stat.valueStyle">{{ stat.value }}</p>
                        <p class="mt-0.5 truncate text-xs text-[var(--muted)]">{{ stat.hint }}</p>
                    </div>
                </article>
            </section>

            <section class="mt-6 grid items-stretch gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.8fr)]">
                <article class="card flex h-full min-h-0 flex-col p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                        <h2 class="text-lg font-bold tracking-tight">{{ t('dashboard.chart') }}</h2>
                        <p class="mt-0.5 text-[13px] text-[var(--muted)]">{{ t('dashboard.hijriYear', { year: data.year }) }}</p>
                        </div>
                    </div>
                    <div class="h-72 shrink-0">
                        <canvas ref="canvas"></canvas>
                    </div>
                    <div class="mt-4 min-h-0 flex-1 overflow-auto">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>{{ t('reports.month') }}</th>
                                    <th>{{ t('dashboard.chartIncome') }}</th>
                                    <th>{{ t('dashboard.chartExpenses') }}</th>
                                    <th>{{ t('reports.balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in data.chart" :key="row.month">
                                    <td class="dari font-semibold">{{ row.name }}</td>
                                    <td class="num text-[var(--emerald-ink)]">{{ afn(row.income) }}</td>
                                    <td class="num text-[var(--rose-ink)]">{{ afn(row.expenses) }}</td>
                                    <td class="num font-bold" :class="moneyClass(row.balance)">{{ afn(row.balance) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
                <article class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold tracking-tight">{{ t('dashboard.unpaid') }}</h2>
                        <RouterLink class="text-[13px] font-bold text-[var(--primary-ink)] hover:underline" to="/reports">{{ t('nav.reports') }}</RouterLink>
                    </div>
                    <div v-if="!data.top_unpaid.length" class="mt-6 flex items-center gap-2 text-sm text-[var(--muted)]">
                        <i class="mdi mdi-check-decagram-outline text-lg text-[var(--emerald-ink)]"></i>
                        {{ t('dashboard.allPaid') }}
                    </div>
                    <ul v-else class="mt-3 divide-y divide-[var(--line)]">
                        <li v-for="row in data.top_unpaid" :key="row.student_id" class="flex items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <PersonLink :to="`/students/${row.student_id}`" :name="row.student" />
                                <p class="mt-0.5 text-xs text-[var(--muted)]">{{ row.class }} · {{ t('dashboard.unpaidCount', { count: row.unpaid_count }) }}</p>
                                <p class="dari mt-1 text-xs font-semibold text-[var(--amber-ink)]">{{ row.unpaid_months.map((month) => month.name).join('، ') }}</p>
                            </div>
                            <p class="num shrink-0 text-sm font-bold text-[var(--amber-ink)]">{{ afn(row.total_outstanding) }}</p>
                        </li>
                    </ul>
                </article>
            </section>

            <section class="mt-6 grid gap-4 lg:grid-cols-2">
                <article class="card p-5">
                    <h2 class="text-lg font-bold tracking-tight">{{ t('dashboard.recentFees') }}</h2>
                    <div v-if="!data.recent_payments.length" class="mt-4 text-sm text-[var(--muted)]">{{ t('dashboard.noFees') }}</div>
                    <ul v-else class="mt-3 divide-y divide-[var(--line)]">
                        <li v-for="payment in data.recent_payments" :key="payment.id" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <PersonLink :to="`/students/${payment.student_id}`" :name="payment.student" />
                                <p class="mt-0.5 text-xs text-[var(--muted)]">{{ payment.class }} · <span class="dari">{{ payment.month_name }}</span> · <span class="dari">{{ payment.date }}</span></p>
                            </div>
                            <p class="num shrink-0 text-sm font-bold text-[var(--emerald-ink)]">{{ afn(payment.amount) }}</p>
                        </li>
                    </ul>
                </article>
                <article class="card p-5">
                    <h2 class="text-lg font-bold tracking-tight">{{ t('dashboard.recentExpenses') }}</h2>
                    <div v-if="!data.recent_expenses.length" class="mt-4 text-sm text-[var(--muted)]">{{ t('dashboard.noExpenses') }}</div>
                    <ul v-else class="mt-3 divide-y divide-[var(--line)]">
                        <li v-for="expense in data.recent_expenses" :key="expense.id" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <PersonLink v-if="expense.kind === 'salary'" :to="`/teachers/${expense.teacher_id}`" :name="expense.title" />
                                <p v-else class="font-bold">{{ expense.title }}</p>
                                <p class="mt-0.5 text-xs text-[var(--muted)]">{{ expenseLine(expense) }} · <span class="dari">{{ expense.date }}</span></p>
                            </div>
                            <p class="num shrink-0 text-sm font-bold text-[var(--rose-ink)]">{{ afn(expense.amount) }}</p>
                        </li>
                    </ul>
                </article>
            </section>
        </template>
    </AppLayout>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, Tooltip } from 'chart.js';
import AppLayout from '../layouts/AppLayout.vue';
import PersonLink from '../components/PersonLink.vue';
import http from '../http';
import { afn, moneyClass } from '../format';
import { store } from '../store';
import { t } from '../i18n';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);
Chart.defaults.font.family = "'Plus Jakarta Sans', 'Noto Sans Arabic', sans-serif";
Chart.defaults.font.size = 11;

const loading = ref(true);
const data = ref(null);
const canvas = ref(null);
let chart;

const stats = computed(() => {
    if (!data.value) return [];

    return [
        {
            label: data.value.period_scope === 'month' ? t('dashboard.incomeMonth') : t('dashboard.incomeYear'),
            value: afn(data.value.income_this_month),
            hint: data.value.focus_month_name || String(data.value.year),
            icon: 'mdi-cash-plus',
            iconStyle: iconTile('var(--emerald-ink)'),
            valueStyle: { color: 'var(--emerald-ink)' },
        },
        {
            label: data.value.period_scope === 'month' ? t('dashboard.expensesMonth') : t('dashboard.expensesYear'),
            value: afn(data.value.expenses_this_month),
            hint: t('dashboard.salaryMix'),
            icon: 'mdi-cash-minus',
            iconStyle: iconTile('var(--rose-ink)'),
            valueStyle: { color: 'var(--rose-ink)' },
        },
        {
            label: t('dashboard.balance'),
            value: afn(data.value.current_balance),
            hint: t('dashboard.balanceHint'),
            icon: 'mdi-scale-balance',
            iconStyle: iconTile('var(--primary)'),
            valueStyle: { color: data.value.current_balance < 0 ? 'var(--rose-ink)' : 'var(--primary-ink)' },
        },
        {
            label: t('dashboard.totalExpenses'),
            value: afn(data.value.total_expenses),
            hint: t('dashboard.totalHint'),
            icon: 'mdi-receipt-text-outline',
            iconStyle: iconTile('var(--amber-ink)'),
            valueStyle: { color: 'var(--rose-ink)' },
        },
    ];
});

function iconTile(color) {
    return {
        color,
        background: `color-mix(in srgb, ${color} 13%, transparent)`,
    };
}

async function load() {
    loading.value = true;
    const response = await http.get('/dashboard', { params: { year: store.year } });
    data.value = response.data;
    loading.value = false;
    await nextTick();
    renderChart();
}

function expenseLine(expense) {
    if (expense.kind === 'salary') return `${t('dashboard.salaryLine')} · ${expense.month_name}`;
    return expense.detail;
}

function renderChart() {
    if (!canvas.value || !data.value) return;
    chart?.destroy();
    const rootStyle = getComputedStyle(document.documentElement);
    const muted = rootStyle.getPropertyValue('--muted').trim() || '#6b6659';
    const line = rootStyle.getPropertyValue('--line').trim() || '#dcd6c9';
    const dark = store.theme === 'dark';
    const ink = rootStyle.getPropertyValue('--ink').trim() || '#16150f';

    chart = new Chart(canvas.value, {
        type: 'bar',
        data: {
            labels: data.value.chart.map((row) => row.name),
            datasets: [
                {
                    label: t('dashboard.chartIncome'),
                    data: data.value.chart.map((row) => row.income),
                    backgroundColor: dark ? 'rgba(99, 201, 141, .8)' : '#2f8a5b',
                    borderRadius: 2,
                    borderSkipped: false,
                    maxBarThickness: 26,
                },
                {
                    label: t('dashboard.chartExpenses'),
                    data: data.value.chart.map((row) => row.expenses),
                    backgroundColor: dark ? 'rgba(240, 138, 124, .8)' : '#b8443a',
                    borderRadius: 2,
                    borderSkipped: false,
                    maxBarThickness: 26,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // The canvas inherits `direction: rtl`, which makes Chart.js right-align tick
            // labels and clip their leading digits. Persian month names are single words,
            // so the chart reads fine left-to-right.
            rtl: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: muted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 18 },
                },
                tooltip: {
                    backgroundColor: ink,
                    titleFont: { weight: '700' },
                    padding: 11,
                    cornerRadius: 3,
                    displayColors: false,
                },
            },
            scales: {
                x: {
                    ticks: { color: muted },
                    grid: { display: false },
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: muted, padding: 6 },
                    grid: { color: line },
                    border: { display: false },
                },
            },
        },
    });
}

onMounted(load);
watch(() => store.year, load);
watch(() => store.locale, () => {
    if (data.value) renderChart();
});
watch(() => store.theme, async () => {
    await nextTick();
    if (data.value) renderChart();
});
onBeforeUnmount(() => chart?.destroy());
</script>
