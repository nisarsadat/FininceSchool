import { computed, ref, watch } from 'vue';

const STORAGE_KEY = 'ef_per_page';

// Sizes a user can choose. Kept deliberately small: every option has to stay
// readable inside a table on a school laptop screen.
export const perPageOptions = [10, 25, 50, 100];

function readStoredPerPage() {
    const saved = Number(localStorage.getItem(STORAGE_KEY));

    return perPageOptions.includes(saved) ? saved : 10;
}

// One shared size for the whole app. If a registrar switches to 50 rows on the
// students list, every other table follows immediately, and the choice survives
// a reload, because how many rows are useful is a property of the school rather
// than of the page you happened to open first.
const sharedPerPage = ref(readStoredPerPage());

export function setSharedPerPage(next) {
    const value = Number(next);

    if (!perPageOptions.includes(value) || value === sharedPerPage.value) {
        return;
    }

    sharedPerPage.value = value;
    localStorage.setItem(STORAGE_KEY, String(value));
}

export function usePagination(source, perPage = null) {
    const page = ref(1);
    const size = computed(() => perPage || sharedPerPage.value);

    const total = computed(() => source.value?.length || 0);
    const pages = computed(() => Math.max(1, Math.ceil(total.value / size.value) || 1));
    const from = computed(() => (total.value === 0 ? 0 : (page.value - 1) * size.value + 1));
    const to = computed(() => Math.min(total.value, page.value * size.value));
    const rows = computed(() => {
        const start = (page.value - 1) * size.value;

        return (source.value || []).slice(start, start + size.value);
    });

    // Shrinking the list can strand the reader on a page that no longer exists.
    watch(pages, (count) => {
        if (page.value > count) {
            page.value = count;
        }
    });

    function setPage(next) {
        page.value = Math.min(pages.value, Math.max(1, Number(next) || 1));
    }

    function setPerPage(next) {
        const value = Number(next);

        if (!perPageOptions.includes(value)) {
            return;
        }

        setSharedPerPage(value);
        // A new size makes the old offset meaningless, so return to the first page.
        page.value = 1;
    }

    function reset() {
        page.value = 1;
    }

    return { page, pages, total, from, to, rows, perPage: size, setPerPage, setPage, reset };
}
