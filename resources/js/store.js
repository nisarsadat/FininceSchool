import { reactive } from 'vue';
import http from './http';

export const store = reactive({
    user: null,
    today: null,
    months: [],
    schoolName: 'EduFinance Pro',
    year: localStorage.getItem('ef_year') ? Number(localStorage.getItem('ef_year')) : null,
    locale: localStorage.getItem('ef_locale') || 'en',
    theme: localStorage.getItem('ef_theme') || 'light',
    defaults: { locale: 'en', theme: 'light' },
    toast: null,
});

export function applyDocument() {
    const rtl = store.locale === 'fa' || store.locale === 'ps';
    document.documentElement.lang = store.locale === 'fa' ? 'fa-AF' : store.locale === 'ps' ? 'ps' : 'en';
    document.documentElement.dir = rtl ? 'rtl' : 'ltr';
    document.documentElement.classList.toggle('dark', store.theme === 'dark');
}

export function setLocale(locale) {
    store.locale = locale;
    localStorage.setItem('ef_locale', locale);
    localStorage.setItem('ef_locale_chosen', '1');
    applyDocument();
}

export function setTheme(theme) {
    store.theme = theme;
    localStorage.setItem('ef_theme', theme);
    localStorage.setItem('ef_theme_chosen', '1');
    applyDocument();
}

export function applyDeskDefaults(locale, theme) {
    store.defaults = {
        locale: locale || 'en',
        theme: theme || 'light',
    };

    if (localStorage.getItem('ef_locale_chosen') !== '1' && locale) {
        store.locale = locale;
    }

    if (localStorage.getItem('ef_theme_chosen') !== '1' && theme) {
        store.theme = theme;
    }

    applyDocument();
}

export function can(permission) {
    return Boolean(store.user?.permissions?.includes(permission));
}

let userPromise = null;

export function loadUser() {
    if (store.user) {
        return Promise.resolve(store.user);
    }

    if (!localStorage.getItem('ef_token')) {
        return Promise.resolve(null);
    }

    if (!userPromise) {
        userPromise = http.get('/me').then(({ data }) => {
            store.user = data;
            return data;
        }).catch((error) => {
            userPromise = null;
            throw error;
        });
    }

    return userPromise;
}

let calendarPromise = null;
let toastTimer = null;

export function loadCalendar() {
    if (!calendarPromise) {
        calendarPromise = http.get('/calendar').then(({ data }) => {
            store.today = data.today;
            store.months = data.months;
            store.schoolName = data.school_name || 'EduFinance Pro';
            applyDeskDefaults(data.default_locale, data.default_theme);

            if (!store.year) {
                setYear(data.today.year);
            }
        }).catch((error) => {
            calendarPromise = null;
            throw error;
        });
    }

    return calendarPromise;
}

export function setYear(year) {
    store.year = Number(year);
    localStorage.setItem('ef_year', String(store.year));
}

export function notify(message, type = 'success') {
    store.toast = { message, type };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        store.toast = null;
    }, 2800);
}

export async function logout() {
    try {
        await http.post('/logout');
    } catch {
        // The local session is cleared either way.
    }

    localStorage.removeItem('ef_token');
    store.user = null;
    userPromise = null;
}
