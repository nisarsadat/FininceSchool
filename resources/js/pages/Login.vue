<template>
    <div class="grid min-h-screen bg-[var(--bg)] lg:grid-cols-[1.05fr_1fr]">
        <section class="login-hero relative hidden overflow-hidden px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="relative z-10 flex items-center gap-3">
                <div class="logo-tile">
                    <i class="mdi mdi-finance text-2xl leading-none"></i>
                </div>
                <div>
                    <p class="text-base font-bold leading-tight" dir="ltr">EduFinance Pro</p>
                    <p class="text-[10px] font-bold uppercase tracking-[.18em] text-white/45">{{ t('nav.tagline') }}</p>
                </div>
            </div>

            <div class="relative z-10 max-w-md">
                <h1 class="text-[42px] font-bold leading-[1.15] tracking-tight">{{ t('login.hero') }}</h1>
                <div class="mt-6 h-px w-16 bg-[#7fc4b4]"></div>
                <p class="mt-6 text-[17px] leading-8 text-white/55">{{ t('login.heroText') }}</p>

                <div class="mt-10 flex flex-wrap gap-3">
                    <span class="hero-chip"><i class="mdi mdi-chart-box-plus-outline"></i>{{ t('nav.reports') }}</span>
                    <span class="hero-chip"><i class="mdi mdi-cash-multiple"></i>{{ t('nav.payments') }}</span>
                    <span class="hero-chip"><i class="mdi mdi-shield-check-outline"></i>{{ t('nav.users') }}</span>
                </div>
            </div>

            <p v-if="store.today" class="dari relative z-10 inline-flex w-fit items-center gap-2.5 border border-white/20 px-5 py-3 text-xl font-bold">
                <i class="mdi mdi-calendar-month-outline text-[#7fc4b4]"></i>
                {{ store.today.formatted }}
            </p>
        </section>

        <section class="flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                <div class="mb-4 flex justify-end"><LocaleTheme /></div>
                <form class="card fade-up p-6 sm:p-8" @submit.prevent="submit">
                    <p class="text-[11px] font-bold uppercase tracking-[.18em] text-[var(--primary-ink)]" dir="ltr">EduFinance Pro</p>
                    <h2 class="mt-2 text-[28px] font-bold tracking-tight">{{ t('login.signIn') }}</h2>
                    <p class="mt-1.5 text-sm text-[var(--muted)]">{{ t('login.lead') }}</p>

                    <p v-if="formError" class="notice notice-error mt-5">
                        <i class="mdi mdi-alert-circle-outline text-lg"></i>
                        {{ formError }}
                    </p>

                    <label class="mt-6 block">
                        <span class="label">{{ t('login.code') }}</span>
                        <div class="relative">
                            <i class="mdi mdi-account-outline pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-xl text-[var(--muted)]"></i>
                            <input v-model="form.code" class="field ps-11 uppercase" dir="ltr" autocomplete="username" required>
                        </div>
                    </label>
                    <label class="mt-4 block">
                        <span class="label">{{ t('login.password') }}</span>
                        <div class="relative">
                            <i class="mdi mdi-lock-outline pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-xl text-[var(--muted)]"></i>
                            <input v-model="form.password" class="field ps-11" dir="ltr" type="password" autocomplete="current-password" required>
                        </div>
                    </label>

                    <button class="btn btn-primary mt-7 w-full py-2.5" type="submit" :disabled="busy">
                        <i class="mdi mdi-login text-lg"></i>
                        {{ busy ? t('login.working') : t('login.submit') }}
                    </button>

                    <div class="mt-6 border-s-2 px-4 py-3 text-sm" style="border-color: var(--primary); background: var(--primary-soft)">
                        <p class="font-bold text-[var(--primary-ink)]">{{ t('login.demo') }}</p>
                        <p class="mt-1 flex items-center gap-2 text-[var(--muted)]"><i class="mdi mdi-account text-base"></i>ADMIN</p>
                        <p class="mt-0.5 flex items-center gap-2 text-[var(--muted)]"><i class="mdi mdi-key-variant text-base"></i>password</p>
                    </div>
                </form>
            </div>
        </section>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import LocaleTheme from '../components/LocaleTheme.vue';
import http from '../http';
import { errorMessage } from '../format';
import { store } from '../store';
import { t } from '../i18n';

const router = useRouter();
const busy = ref(false);
const formError = ref('');
const form = reactive({
    code: 'ADMIN',
    password: 'password',
});

async function submit() {
    busy.value = true;
    formError.value = '';

    try {
        const { data } = await http.post('/login', {
            code: form.code.trim(),
            password: form.password,
        });
        localStorage.setItem('ef_token', data.token);
        store.user = data.user;
        router.push('/');
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        busy.value = false;
    }
}
</script>

<style scoped>
/* The hero is an ink masthead ruled like a ledger page, not a gradient wash. */
.login-hero {
    background: #16150f;
    border-inline-end: 1px solid rgba(244, 241, 233, .12);
}

/* Faint ledger ruling, kept low-contrast so it reads as texture, not stripes. */
.login-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: linear-gradient(rgba(244, 241, 233, .022) 1px, transparent 1px);
    background-size: 100% 34px;
}

.logo-tile {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 3px;
    color: #f4f1e9;
    border: 1px solid rgba(244, 241, 233, .35);
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 13px;
    border-radius: 2px;
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: .02em;
    color: #cfc9ba;
    border: 1px solid rgba(244, 241, 233, .18);
}

.hero-chip .mdi {
    font-size: 16px;
    color: #7fc4b4;
}
</style>
