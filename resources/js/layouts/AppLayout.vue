<template>
    <div class="shell" :class="{ 'rail-collapsed': collapsed, 'rail-open': open }">
        <Transition name="nav-fade">
            <div v-if="open" class="rail-backdrop" @click="open = false"></div>
        </Transition>

        <aside class="rail" :aria-label="t('common.menu')">
            <RouterLink class="rail-brand" to="/" :aria-label="t('nav.dashboard')" @click="open = false">
                <span class="rail-mark">
                    <img :src="schoolLogo" alt="" width="40" height="40">
                </span>
                <span class="rail-brand-text">
                    <span class="rail-brand-name" dir="auto">{{ store.schoolName }}</span>
                    <span class="rail-brand-sub" dir="ltr">EduFinance Pro</span>
                </span>
            </RouterLink>

            <nav ref="railNav" class="rail-nav">
                <span class="rail-thumb" :style="thumbStyle" aria-hidden="true"></span>

                <section v-for="group in groups" :key="group.key" class="rail-group">
                    <p class="rail-group-label">{{ t(group.key) }}</p>
                    <RouterLink
                        v-for="link in group.links"
                        :key="link.to"
                        :to="link.to"
                        class="rail-link"
                        :data-active="String(isActive(link.to))"
                        @click="open = false"
                    >
                        <i class="mdi" :class="link.icon"></i>
                        <span class="rail-link-text">{{ t(link.key) }}</span>
                    </RouterLink>
                </section>
            </nav>

            <div class="rail-foot">
                <span class="rail-avatar">{{ userInitials }}</span>
                <span class="rail-foot-meta">
                    <span class="rail-foot-name">{{ store.user?.name }}</span>
                    <span v-if="store.user?.code" class="rail-foot-code" dir="ltr">{{ store.user.code }}</span>
                </span>
                <button class="rail-logout" type="button" :aria-label="t('common.logout')" @click="signOut">
                    <i class="mdi mdi-logout-variant"></i>
                </button>
            </div>
        </aside>

        <header class="topbar">
            <button class="icon-btn" type="button" :aria-label="menuHidden ? t('common.showMenu') : t('common.hideMenu')" :aria-expanded="String(!menuHidden)" @click="toggleMenu">
                <i class="mdi text-2xl" :class="menuHidden ? 'mdi-menu' : 'mdi-menu-open'"></i>
            </button>

            <p v-if="store.today" class="topbar-date dari">{{ store.today.formatted }}</p>

            <GlobalSearch />

            <div class="top-tools">
                <label class="hidden items-center gap-2 text-xs font-bold uppercase tracking-wide text-[var(--muted)] md:flex">
                    {{ t('common.year') }}
                    <select class="field w-24 px-2.5 py-1.5 text-sm" :value="store.year" @change="setYear($event.target.value)">
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </label>
                <LocaleTheme />
            </div>
        </header>

        <main class="main">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import logoUrl from '../../../public/android-chrome-192x192.png';
import GlobalSearch from '../components/GlobalSearch.vue';
import LocaleTheme from '../components/LocaleTheme.vue';
import { t } from '../i18n';
import { linkAllowed, navGroups } from '../nav';
import { can, loadUser, logout, setYear, store } from '../store';

// Import the mark so Vite hashes it into /build/assets: plain /public URLs
// fall through to the SPA on Vercel and come back as HTML, which renders a
// blank image.
const schoolLogo = logoUrl;
const open = ref(false);
const collapsed = ref(localStorage.getItem('ef_nav_open') === '0');
const wide = ref(window.matchMedia('(min-width: 1024px)').matches);
const menuHidden = computed(() => (wide.value ? collapsed.value : !open.value));

const route = useRoute();
const router = useRouter();

const railNav = ref(null);
const thumb = ref({ y: 0, show: false });
const thumbStyle = computed(() => ({
    transform: `translateY(${thumb.value.y}px)`,
    opacity: thumb.value.show ? '1' : '0',
}));

const groups = computed(() => navGroups
    .map((group) => ({
        key: group.key,
        links: group.links.filter((link) => !store.user || linkAllowed(link, can)),
    }))
    .filter((group) => group.links.length));

function toggleMenu() {
    if (wide.value) {
        collapsed.value = !collapsed.value;
        localStorage.setItem('ef_nav_open', collapsed.value ? '0' : '1');
        return;
    }

    open.value = !open.value;
}

const userInitials = computed(() => {
    const name = store.user?.name || '';
    if (!name) return '?';
    return name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
});

const years = computed(() => {
    const current = store.today?.year || store.year || 1405;
    const list = [];

    for (let year = current - 3; year <= current + 1; year += 1) {
        list.push(year);
    }

    return list;
});

function isActive(path) {
    if (path === '/') return route.path === '/';
    return route.path === path || route.path.startsWith(`${path}/`);
}

// One thumb slides between links, so it is re-aimed after every route, locale, or font change.
function placeThumb() {
    const nav = railNav.value;
    if (!nav) return;

    const active = nav.querySelector('.rail-link[data-active="true"]');

    if (!active) {
        thumb.value = { y: 0, show: false };
        return;
    }

    thumb.value = { y: active.offsetTop, show: true };
}

async function signOut() {
    await logout();
    router.push('/login');
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

let desktopQuery;

function syncWide() {
    wide.value = desktopQuery.matches;

    if (wide.value) {
        open.value = false;
    }

    nextTick(placeThumb);
}

watch(() => route.path, () => nextTick(placeThumb));
watch([groups, () => store.locale], () => nextTick(placeThumb));

onBeforeUnmount(() => {
    desktopQuery?.removeEventListener('change', syncWide);
    document.removeEventListener('keydown', onKeydown);
});

onMounted(async () => {
    desktopQuery = window.matchMedia('(min-width: 1024px)');
    syncWide();
    desktopQuery.addEventListener('change', syncWide);

    document.addEventListener('keydown', onKeydown);
    placeThumb();
    nextTick(placeThumb);
    document.fonts?.ready.then(placeThumb);

    if (store.user) return;

    try {
        await loadUser();
        nextTick(placeThumb);
    } catch {
        router.push('/login');
    }
});
</script>
