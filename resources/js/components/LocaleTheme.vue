<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="seg" role="group" :aria-label="t('common.language')">
            <button v-for="option in locales" :key="option.value" type="button" class="seg-item" :class="{ 'seg-active': store.locale === option.value }" @click="setLocale(option.value)">
                {{ option.label }}
            </button>
        </div>
        <button type="button" class="theme-toggle" :aria-label="t('common.theme')" @click="setTheme(store.theme === 'dark' ? 'light' : 'dark')">
            <i class="mdi text-lg" :class="store.theme === 'dark' ? 'mdi-weather-night' : 'mdi-white-balance-sunny'"></i>
        </button>
    </div>
</template>

<script setup>
import { setLocale, setTheme, store } from '../store';
import { t } from '../i18n';

const locales = [
    { value: 'en', label: 'EN' },
    { value: 'fa', label: 'دری' },
    { value: 'ps', label: 'پښتو' },
];
</script>

<style scoped>
.seg {
    display: inline-flex;
    border-radius: 3px;
    background: transparent;
    border: 1px solid var(--line);
    overflow: hidden;
}

.seg-item {
    padding: 5px 10px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--muted);
    transition: color .15s ease, background-color .15s ease;
    white-space: nowrap;
}

.seg-item + .seg-item {
    border-inline-start: 1px solid var(--line);
}

.seg-item:hover {
    color: var(--ink);
    background: var(--surface-2);
}

.seg-active {
    background: var(--primary);
    color: #fff;
}

.theme-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 3px;
    border: 1px solid var(--line);
    background: transparent;
    color: var(--muted);
    transition: color .15s ease, background-color .15s ease, border-color .15s ease;
}

.theme-toggle:hover {
    color: var(--ink);
    background: var(--surface-2);
    border-color: var(--muted);
}
</style>
