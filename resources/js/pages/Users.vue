<template>
    <AppLayout>
        <div class="page-head flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ t('users.title') }}</h1>
                <p class="mt-1 max-w-3xl text-[var(--muted)]">{{ t('users.lead') }}</p>
            </div>
            <button v-if="can('users.manage')" class="btn btn-primary" type="button" @click="openCreate">
                <i class="mdi mdi-account-plus-outline text-lg"></i>
                {{ t('users.add') }}
            </button>
        </div>

        <div v-if="can('users.manage')" class="card mb-6 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="tbl min-w-[640px]">
                <thead>
                    <tr>
                        <th>{{ t('common.name') }}</th>
                        <th>{{ t('users.code') }}</th>
                        <th>{{ t('common.role') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="person in rows" :key="person.id">
                        <td class="font-bold">{{ person.name }}</td>
                        <td>{{ person.code }}</td>
                        <td>{{ roleLabel(person.role) }}</td>
                        <td>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold ring-1" :class="statusClass(person.is_active ? 'active' : 'inactive')">
                                {{ person.is_active ? t('status.active') : t('status.inactive') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <RowMenu :items="userItems(person)" />
                        </td>
                    </tr>
                    <tr v-if="!people.length">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="5">{{ t('users.empty') }}</td>
                    </tr>
                </tbody>
            </table>
            </div>
            <Pagination :page="page" :pages="pages" :total="total" :from="from" :to="to" @update:page="setPage" :per-page="perPage" @update:per-page="setPerPage" />
        </div>

        <section v-if="can('roles.manage')" class="card p-5">
            <div class="page-head flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold">{{ t('users.rolesTitle') }}</h2>
                    <p class="mt-1 text-[var(--muted)]">{{ t('users.rolesLead') }}</p>
                </div>
                <button class="btn btn-primary" type="button" @click="openRole">
                    <i class="mdi mdi-shield-plus-outline text-lg"></i>
                    {{ t('users.addRole') }}
                </button>
            </div>
            <div class="tabs mt-4">
                <button v-for="role in roles" :key="role.id" class="tab" :class="selectedId === role.id ? 'tab-active' : ''" type="button" @click="selectRole(role)">
                    {{ roleLabel(role) }}
                </button>
            </div>
            <p v-if="selected?.slug === 'admin'" class="notice notice-info mt-4">{{ t('users.adminLocked') }}</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <fieldset v-for="group in groups" :key="group.id" class="rounded-lg border border-[var(--line)] p-4">
                    <legend class="px-1 font-bold">{{ group.label }}</legend>
                    <label v-for="item in group.items" :key="item.slug" class="mt-2 flex items-start gap-2 text-sm">
                        <input v-model="draft" class="mt-1" type="checkbox" :value="item.slug" :disabled="selected?.slug === 'admin'">
                        <span>{{ t(`perm.${item.slug}`) }}</span>
                    </label>
                </fieldset>
            </div>
            <button v-if="selected && selected.slug !== 'admin'" class="btn btn-primary mt-4" type="button" :disabled="savingRole" @click="saveRole">
                <i class="mdi mdi-shield-check-outline text-lg"></i>
                {{ savingRole ? t('common.saving') : t('common.save') }}
            </button>
        </section>

        <Modal :open="formOpen" :title="form.id ? t('users.edit') : t('users.add')" @close="formOpen = false">
            <p v-if="formError" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ formError }}</p>
            <div class="grid gap-3">
                <label><span class="label">{{ t('common.name') }}</span><input v-model="form.name" class="field"></label>
                <label><span class="label">{{ t('users.code') }}</span><input v-model="form.code" class="field uppercase" autocomplete="off"></label>
                <label>
                    <span class="label">{{ t('users.password') }}</span>
                    <input v-model="form.password" class="field" type="password" autocomplete="new-password">
                    <p v-if="form.id" class="mt-1 text-xs text-[var(--muted)]">{{ t('users.passwordHint') }}</p>
                </label>
                <label><span class="label">{{ t('users.confirm') }}</span><input v-model="form.password_confirmation" class="field" type="password" autocomplete="new-password"></label>
                <label>
                    <span class="label">{{ t('common.role') }}</span>
                    <select v-model="form.role_id" class="field">
                        <option value="">{{ t('common.role') }}</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ roleLabel(role) }}</option>
                    </select>
                </label>
                <label class="flex items-center gap-2 font-bold">
                    <input v-model="form.is_active" type="checkbox">
                    {{ t('users.active') }}
                </label>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="formOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="saving" @click="save">
                    <i class="mdi mdi-content-save-outline text-lg"></i>
                    {{ saving ? t('common.saving') : t('common.save') }}
                </button>
            </template>
        </Modal>

        <Modal :open="roleOpen" :title="t('users.addRole')" @close="roleOpen = false">
            <p v-if="roleError" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ roleError }}</p>
            <label><span class="label">{{ t('users.roleName') }}</span><input v-model="roleForm.name" class="field"></label>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <fieldset v-for="group in groups" :key="group.id" class="rounded-lg border border-[var(--line)] p-4">
                    <legend class="px-1 font-bold">{{ group.label }}</legend>
                    <label v-for="item in group.items" :key="item.slug" class="mt-2 flex items-start gap-2 text-sm">
                        <input v-model="roleForm.permissions" class="mt-1" type="checkbox" :value="item.slug">
                        <span>{{ t(`perm.${item.slug}`) }}</span>
                    </label>
                </fieldset>
            </div>
            <template #footer>
                <button class="btn btn-ghost" type="button" @click="roleOpen = false">{{ t('common.cancel') }}</button>
                <button class="btn btn-primary" type="button" :disabled="savingRole" @click="createRole">
                    <i class="mdi mdi-shield-plus-outline text-lg"></i>
                    {{ savingRole ? t('common.saving') : t('common.save') }}
                </button>
            </template>
        </Modal>

        <ConfirmDialog :open="confirmOpen" :title="t('users.deleteTitle')" :message="confirmMessage" :confirm-label="t('common.delete')" :busy="saving" @close="confirmOpen = false" @confirm="remove" />
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import Modal from '../components/Modal.vue';
import Pagination from '../components/Pagination.vue';
import RowMenu from '../components/RowMenu.vue';
import { usePagination } from '../pagination';
import http from '../http';
import { errorMessage, statusClass } from '../format';
import { t } from '../i18n';
import { can, loadUser, notify, store } from '../store';

const people = ref([]);
const { page, pages, total, from, to, rows, setPage, perPage, setPerPage } = usePagination(people);
const roles = ref([]);
const catalog = ref([]);
const selectedId = ref(null);
const draft = ref([]);
const formOpen = ref(false);
const confirmOpen = ref(false);
const confirmMessage = ref('');
const pending = ref(null);
const saving = ref(false);
const savingRole = ref(false);
const roleOpen = ref(false);
const roleError = ref('');
const roleForm = reactive({ name: '', permissions: [] });
const formError = ref('');
const form = reactive(blank());

const groupNames = {
    dashboard: 'nav.dashboard',
    students: 'nav.students',
    teachers: 'nav.teachers',
    classes: 'nav.classes',
    payments: 'nav.payments',
    expenses: 'nav.expenses',
    accounts: 'nav.accounts',
    reports: 'nav.reports',
    settings: 'nav.settings',
    users: 'nav.users',
    roles: 'users.rolesTitle',
};

const selected = computed(() => roles.value.find((role) => role.id === selectedId.value) || null);

const groups = computed(() => {
    const map = new Map();

    catalog.value.forEach((item) => {
        if (!map.has(item.group)) {
            map.set(item.group, []);
        }

        map.get(item.group).push(item);
    });

    return [...map.entries()].map(([id, items]) => ({
        id,
        label: t(groupNames[id] || id),
        items,
    }));
});

function blank() {
    return {
        id: null,
        name: '',
        code: '',
        password: '',
        password_confirmation: '',
        role_id: '',
        is_active: true,
    };
}

function roleLabel(role) {
    if (!role) return '—';
    const key = `role.${role.slug}`;
    const label = t(key);
    return label === key ? role.name : label;
}

function userItems(person) {
    return [
        { icon: 'mdi-pencil-outline', label: t('common.edit'), onClick: () => openEdit(person) },
        { icon: 'mdi-delete-outline', label: t('common.delete'), danger: true, show: person.id !== store.user?.id, onClick: () => askDelete(person) },
    ];
}

function selectRole(role) {
    selectedId.value = role.id;
    draft.value = [...(role.permissions || [])];
}

async function load() {
    const requests = [];

    if (can('users.manage')) {
        requests.push(http.get('/users').then(({ data }) => {
            people.value = data;
        }));
    }

    if (can('users.manage') || can('roles.manage')) {
        requests.push(http.get('/roles').then(({ data }) => {
            roles.value = data.roles;
            catalog.value = data.catalog;

            if (!selectedId.value && data.roles.length) {
                selectRole(data.roles[0]);
            } else if (selected.value) {
                const fresh = data.roles.find((role) => role.id === selectedId.value);
                if (fresh) selectRole(fresh);
            }
        }));
    }

    await Promise.all(requests);
}

function openCreate() {
    Object.assign(form, blank());
    form.role_id = roles.value.find((role) => role.slug === 'viewer')?.id || roles.value[0]?.id || '';
    formError.value = '';
    formOpen.value = true;
}

function openEdit(person) {
    Object.assign(form, {
        id: person.id,
        name: person.name,
        code: person.code,
        password: '',
        password_confirmation: '',
        role_id: person.role?.id || '',
        is_active: person.is_active,
    });
    formError.value = '';
    formOpen.value = true;
}

function askDelete(person) {
    pending.value = person;
    confirmMessage.value = t('users.deleteMessage', { name: person.name });
    confirmOpen.value = true;
}

async function save() {
    saving.value = true;
    formError.value = '';
    const payload = {
        name: form.name,
        code: form.code.trim(),
        password: form.password,
        password_confirmation: form.password_confirmation,
        role_id: form.role_id,
        is_active: form.is_active,
    };

    try {
        if (form.id) {
            await http.put(`/users/${form.id}`, payload);
        } else {
            await http.post('/users', payload);
        }

        formOpen.value = false;
        notify(t('users.saved'));
        await load();

        if (form.id === store.user?.id) {
            store.user = null;
            await loadUser();
        }
    } catch (error) {
        formError.value = errorMessage(error);
    } finally {
        saving.value = false;
    }
}

async function remove() {
    if (!pending.value) return;
    saving.value = true;

    try {
        await http.delete(`/users/${pending.value.id}`);
        confirmOpen.value = false;
        notify(t('users.deleted'));
        await load();
    } catch (error) {
        confirmOpen.value = false;
        notify(errorMessage(error), 'error');
    } finally {
        saving.value = false;
    }
}

function openRole() {
    roleForm.name = '';
    roleForm.permissions = [];
    roleError.value = '';
    roleOpen.value = true;
}

async function createRole() {
    savingRole.value = true;
    roleError.value = '';

    try {
        const { data } = await http.post('/roles', {
            name: roleForm.name,
            permissions: roleForm.permissions,
        });
        roleOpen.value = false;
        notify(t('users.roleCreated'));
        await load();
        const created = roles.value.find((role) => role.id === data.id);
        if (created) selectRole(created);
    } catch (error) {
        roleError.value = errorMessage(error);
    } finally {
        savingRole.value = false;
    }
}

async function saveRole() {
    if (!selected.value) return;
    savingRole.value = true;

    try {
        await http.put(`/roles/${selected.value.id}`, { permissions: draft.value });
        notify(t('users.savedRole'));
        await load();

        if (store.user?.role?.id === selected.value.id) {
            store.user = null;
            await loadUser();
        }
    } catch (error) {
        notify(errorMessage(error), 'error');
    } finally {
        savingRole.value = false;
    }
}

onMounted(load);
</script>
