<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('expenses.title') }}</h1>
                <p class="mt-1 text-[var(--muted)]">{{ t('expenses.lead', { year: store.year }) }}</p>
            </div>
        </div>
        <div class="tabs mb-5">
            <button class="tab" :class="tab === 'list' ? 'tab-active' : ''" type="button" @click="setTab('list')">{{ t('expenses.list') }}</button>
            <button class="tab" :class="tab === 'categories' ? 'tab-active' : ''" type="button" @click="setTab('categories')">{{ t('expenses.categories') }}</button>
        </div>
        <Expenses v-if="tab === 'list'" />
        <ExpenseCategories v-else />
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import Expenses from './Expenses.vue';
import ExpenseCategories from './ExpenseCategories.vue';
import { t } from '../i18n';
import { store } from '../store';

const route = useRoute();
const router = useRouter();
const tab = computed(() => (route.query.tab === 'categories' ? 'categories' : 'list'));

function setTab(next) {
    router.replace({ path: '/expenses', query: { tab: next } });
}
</script>
