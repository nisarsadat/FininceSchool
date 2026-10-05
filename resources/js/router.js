import { createRouter, createWebHistory } from 'vue-router';
import { linkAllowed, navLinks } from './nav';
import { can, loadCalendar, loadUser, store } from './store';
import Login from './pages/Login.vue';
import Dashboard from './pages/Dashboard.vue';
import Students from './pages/Students.vue';
import Teachers from './pages/Teachers.vue';
import Classes from './pages/Classes.vue';
import Payments from './pages/Payments.vue';
import ExpenseDesk from './pages/ExpenseDesk.vue';
import Accounts from './pages/Accounts.vue';
import Reports from './pages/Reports.vue';
import Settings from './pages/Settings.vue';
import Users from './pages/Users.vue';
import StudentProfile from './pages/StudentProfile.vue';
import TeacherProfile from './pages/TeacherProfile.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/register', redirect: '/login' },
        { path: '/', name: 'dashboard', component: Dashboard, meta: { auth: true, permission: 'dashboard.view' } },
        { path: '/students', name: 'students', component: Students, meta: { auth: true, permission: 'students.view' } },
        { path: '/students/:id', name: 'student-profile', component: StudentProfile, meta: { auth: true, permission: 'students.view' } },
        { path: '/teachers', name: 'teachers', component: Teachers, meta: { auth: true, permission: 'teachers.view' } },
        { path: '/teachers/:id', name: 'teacher-profile', component: TeacherProfile, meta: { auth: true, permission: 'teachers.view' } },
        { path: '/classes', name: 'classes', component: Classes, meta: { auth: true, permission: 'classes.view' } },
        { path: '/payments', name: 'payments', component: Payments, meta: { auth: true, permission: 'payments.view' } },
        { path: '/fees', redirect: (to) => ({ path: '/payments', query: { ...to.query, tab: 'fees' } }) },
        { path: '/salaries', redirect: { path: '/payments', query: { tab: 'salaries' } } },
        { path: '/expenses', name: 'expenses', component: ExpenseDesk, meta: { auth: true, permission: 'expenses.view' } },
        { path: '/expense-categories', redirect: { path: '/expenses', query: { tab: 'categories' } } },
        { path: '/accounts', name: 'accounts', component: Accounts, meta: { auth: true, permission: 'accounts.view' } },
        { path: '/reports', name: 'reports', component: Reports, meta: { auth: true, permission: 'reports.view' } },
        { path: '/settings', name: 'settings', component: Settings, meta: { auth: true, permission: 'settings.manage' } },
        { path: '/users', name: 'users', component: Users, meta: { auth: true, any: ['users.manage', 'roles.manage'] } },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

function routeAllowed(to) {
    if (to.meta.any) {
        return to.meta.any.some((permission) => can(permission));
    }

    if (to.meta.permission) {
        return can(to.meta.permission);
    }

    return true;
}

function firstDesk() {
    return navLinks.find((link) => linkAllowed(link, can))?.to || '/login';
}

router.beforeEach(async (to) => {
    const token = localStorage.getItem('ef_token');

    if (to.meta.auth && !token) {
        return { name: 'login' };
    }

    if (token && to.meta.auth && !store.user) {
        try {
            await loadUser();
        } catch {
            return { name: 'login' };
        }
    }

    if (to.meta.guest && token) {
        if (!store.user) {
            try {
                await loadUser();
            } catch {
                localStorage.removeItem('ef_token');
                return;
            }
        }

        return firstDesk();
    }

    if (to.meta.auth && store.user && !routeAllowed(to)) {
        const next = firstDesk();

        if (next && next !== to.path) {
            return next;
        }
    }

    try {
        await loadCalendar();
    } catch {
        // The page can still render if the calendar request fails.
    }
});

export default router;
