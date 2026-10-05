<template>
    <div>
        <span class="label">{{ label || t('common.afghanDate') }}</span>
        <div class="grid grid-cols-3 gap-2">
            <select v-model.number="model.day" class="field">
                <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
            </select>
            <select v-model.number="model.month" class="field dari">
                <option v-for="month in store.months" :key="month.number" :value="month.number">{{ month.name }}</option>
            </select>
            <select v-model.number="model.year" class="field">
                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
            </select>
        </div>
        <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { monthLength } from '../calendar';
import { store } from '../store';
import { t } from '../i18n';

const model = defineModel({ type: Object, required: true });

defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
});

const years = computed(() => {
    const current = store.today?.year || 1405;
    const list = [];

    for (let year = current - 15; year <= current + 1; year += 1) {
        list.push(year);
    }

    return list;
});

const days = computed(() => {
    const length = monthLength(model.value.year || store.today?.year || 1405, model.value.month || 1);

    return Array.from({ length }, (_, index) => index + 1);
});

watch(days, (options) => {
    if (!options.includes(model.value.day)) {
        model.value.day = options[options.length - 1];
    }
});
</script>
