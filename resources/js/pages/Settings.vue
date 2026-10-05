<template>
    <AppLayout>
        <div class="page-head">
            <h1 class="page-title">{{ t('settings.title') }}</h1>
            <p class="mt-1 text-[var(--muted)]">{{ t('settings.lead') }}</p>
        </div>
        <form class="card max-w-xl space-y-4 p-5" @submit.prevent="save">
            <p v-if="formError" class="notice notice-error">
                <i class="mdi mdi-alert-circle-outline text-lg"></i>{{ formError }}
            </p>
            <label><span class="label">{{ t('settings.school') }}</span><input v-model="form.school_name" class="field" required></label>
            <label><span class="label">{{ t('settings.address') }}</span><input v-model="form.address" class="field"></label>
            <label><span class="label">{{ t('settings.phone') }}</span><input v-model="form.phone" class="field"></label>
            <label><span class="label">{{ t('settings.email') }}</span><input v-model="form.email" class="field" type="email"></label>
            <div class="rounded-lg border border-[var(--line)] p-4">
                <p class="font-bold">{{ t('settings.defaults') }}</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label>
                        <span class="label">{{ t('settings.defaultLanguage') }}</span>
                        <select v-model="form.default_locale" class="field">
                            <option value="en">English</option>
                            <option value="fa">دری</option>
                            <option value="ps">پښتو</option>
                        </select>
                        <p class="mt-1 text-xs text-[var(--muted)]">{{ t('settings.languageHint') }}</p>
                    </label>
                    <label>
                        <span class="label">{{ t('settings.defaultTheme') }}</span>
                        <select v-model="form.default_theme" class="field">
                            <option value="light">{{ t('common.light') }}</option>
                            <option value="dark">{{ t('common.dark') }}</option>
                        </select>
                        <p class="mt-1 text-xs text-[var(--muted)]">{{ t('settings.themeHint') }}</p>
                    </label>
                </div>
            </div>
            <div class="grid gap-3 rounded border border-[var(--line)] bg-[var(--surface-2)] p-4 text-sm sm:grid-cols-2">
                <p><span class="text-[var(--muted)]">{{ t('settings.currency') }}</span><br><strong>اف</strong></p>
                <p><span class="text-[var(--muted)]">{{ t('settings.calendar') }}</span><br><strong>حمل تا حوت</strong></p>
            </div>
            <button class="btn btn-primary" type="submit" :disabled="saving">
                <i class="mdi mdi-content-save-outline text-lg"></i>
                {{ saving ? t('common.saving') : t('common.save') }}
            </button>
        </form>
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import http from '../http';
import { errorMessage } from '../format';
import { applyDeskDefaults, notify, store } from '../store';
import { t } from '../i18n';

const saving = ref(false);
const formError = ref('');
const form = reactive({
    school_name: '',
    address: '',
    phone: '',
    email: '',
    default_locale: 'en',
    default_theme: 'light',
});

async function load() {
    const { data } = await http.get('/settings');
    Object.assign(form, {
        school_name: data.school_name || '',
        address: data.address || '',
        phone: data.phone || '',
        email: data.email || '',
        default_locale: data.default_locale || 'en',
        default_theme: data.default_theme || 'light',
    });
}

async function save() {
    saving.value = true;
    formError.value = '';
    try {
        const { data } = await http.put('/settings', form);
        store.schoolName = data.school_name;
        applyDeskDefaults(data.default_locale, data.default_theme);
        notify(t('settings.saved'));
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>
