<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="page-title">{{ t('payments.title') }}</h1>
            <p class="mt-1 text-[var(--muted)]">{{ t('payments.lead') }}</p>
        </div>
        <div class="tabs mb-5">
            <button class="tab" :class="tab === 'fees' ? 'tab-active' : ''" type="button" @click="setTab('fees')">{{ t('payments.fees') }}</button>
            <button class="tab" :class="tab === 'salaries' ? 'tab-active' : ''" type="button" @click="setTab('salaries')">{{ t('payments.salaries') }}</button>
        </div>
        <StudentFees v-if="tab === 'fees'" />
        <TeacherSalaries v-else />
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import StudentFees from './StudentFees.vue';
import TeacherSalaries from './TeacherSalaries.vue';
import { t } from '../i18n';

const route = useRoute();
const router = useRouter();
const tab = computed(() => (route.query.tab === 'salaries' ? 'salaries' : 'fees'));

function setTab(next) {
    router.replace({ path: '/payments', query: { ...route.query, tab: next } });
}
</script>
