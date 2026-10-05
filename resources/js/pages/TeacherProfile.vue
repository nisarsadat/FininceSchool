<template>
    <AppLayout>
        <button class="btn btn-ghost btn-sm mb-4" type="button" @click="router.push('/teachers')">{{ t('common.back') }}</button>
        <div v-if="loading" class="text-[var(--muted)]">{{ t('profile.loading') }}</div>
        <template v-else-if="profile">
            <section class="card overflow-hidden">
                <div class="profile-hero flex flex-wrap items-center gap-5 px-6 py-7">
                    <span class="hero-avatar">{{ initials(profile.teacher.name) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10.5px] font-bold uppercase tracking-[.16em] text-[#7fc4b4]">{{ t('profile.personal') }}</p>
                        <h1 class="mt-1.5 text-[28px] font-bold tracking-tight sm:text-[32px]">{{ profile.teacher.name }}</h1>
                        <p class="mt-1 text-sm text-white/55">{{ profile.teacher.father_name }}</p>
                    </div>
                    <RouterLink class="btn border-white/25 bg-transparent text-white hover:bg-white hover:text-[#16150f]" :to="`/payments?tab=salaries&teacher=${profile.teacher.id}&manual=1`">{{ t('salaries.manual') }}</RouterLink>
                </div>
                <div class="grid gap-x-6 gap-y-4 p-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div><p class="label mb-1 normal-case tracking-normal">{{ t('teachers.joined') }}</p><p class="text-base font-bold">{{ profile.teacher.join_hijri?.formatted }}</p></div>
                    <div><p class="label mb-1 normal-case tracking-normal">{{ t('teachers.salary') }}</p><p class="num text-base font-bold text-[var(--rose-ink)]">{{ afn(profile.teacher.monthly_salary) }}</p></div>
                    <div><p class="label mb-1 normal-case tracking-normal">{{ t('salaries.unpaid') }}</p><p class="num text-base font-bold text-[var(--amber-ink)]">{{ afn(profile.ledger.total_outstanding) }}</p></div>
                    <div><p class="label mb-1 normal-case tracking-normal">{{ t('profile.totalPaid') }}</p><p class="num text-base font-bold">{{ afn(totalPaid) }}</p></div>
                    <div><p class="label mb-1 normal-case tracking-normal">{{ t('common.status') }}</p><span :class="statusClass(profile.teacher.status)">{{ statusLabel(profile.teacher.status) }}</span></div>
                    <div class="sm:col-span-2"><p class="label mb-1 normal-case tracking-normal">{{ t('common.details') }}</p><p class="text-[var(--muted)]">{{ profile.teacher.details || t('common.noNotes') }}</p></div>
                </div>
            </section>

            <h2 class="page-title mb-3 mt-8">{{ t('profile.months') }} · <span class="text-[var(--muted)]">{{ profile.year }}</span></h2>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="month in profile.ledger.months" :key="month.month" class="card p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-lg font-bold tracking-tight">{{ month.name }}</h3>
                        <span :class="statusClass(month.status)">{{ statusLabel(month.status) }}</span>
                    </div>
                    <p v-if="month.payment" class="mt-2 text-sm text-[var(--muted)]">{{ t('fees.paidOn', { date: month.payment.receipt_label }) }} · <span class="num font-bold text-[var(--rose-ink)]">{{ afn(month.payment.amount) }}</span></p>
                    <p v-else class="mt-2 text-sm text-[var(--muted)]">{{ month.status === 'unpaid' ? t('salaries.unpaidMonth') : t('fees.later') }}</p>
                    <RouterLink v-if="!month.payment" class="btn btn-danger btn-sm mt-3" :to="`/payments?tab=salaries&teacher=${profile.teacher.id}&month=${month.month}`">
                        <i class="mdi mdi-cash-minus"></i>{{ t('salaries.pay') }}
                    </RouterLink>
                </article>
            </div>

            <h2 class="page-title mb-3 mt-8">{{ t('profile.history') }}</h2>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="tbl min-w-[680px]">
                        <thead>
                            <tr>
                                <th>{{ t('common.year') }}</th>
                                <th>{{ t('profile.forMonth') }}</th>
                                <th>{{ t('profile.receipt') }}</th>
                                <th>{{ t('common.account') }}</th>
                                <th>{{ t('common.notes') }}</th>
                                <th>{{ t('common.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in rows" :key="payment.id">
                                <td class="num">{{ payment.hijri_year }}</td>
                                <td class="text-base font-bold">{{ payment.month_name }}</td>
                                <td class="num">{{ payment.receipt_label }}</td>
                                <td>{{ payment.account?.name }}</td>
                                <td class="text-[var(--muted)]">{{ payment.notes || '—' }}</td>
                                <td class="num font-bold text-[var(--rose-ink)]">{{ afn(payment.amount) }}</td>
                            </tr>
                            <tr v-if="!profile.payments.length"><td class="py-10 text-center text-[var(--muted)]" colspan="6">{{ t('profile.noHistory') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
            </div>
        </template>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import Pagination from '../components/Pagination.vue';
import http from '../http';
import { usePagination } from '../pagination';
import { afn, statusClass, statusLabel } from '../format';
import { t } from '../i18n';
import { store } from '../store';

const route = useRoute();
const router = useRouter();
const profile = ref(null);
const loading = ref(true);
const payments = computed(() => profile.value?.payments || []);
const { page, pages, total, from, to, rows, setPage, reset, perPage, setPerPage } = usePagination(payments);
const totalPaid = computed(() => payments.value.reduce((sum, payment) => sum + Number(payment.amount || 0), 0));

function initials(name) {
    return (name || '?').trim().slice(0, 1);
}

async function load() {
    loading.value = true;
    const { data } = await http.get(`/teachers/${route.params.id}/profile`, { params: { year: store.year } });
    profile.value = data;
    reset();
    loading.value = false;
}

onMounted(load);
watch(() => [route.params.id, store.year], load);
</script>
