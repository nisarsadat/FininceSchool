const groups = [
    {
        key: 'nav.group.overview',
        links: [
            { to: '/', key: 'nav.dashboard', permission: 'dashboard.view', icon: 'mdi-view-dashboard-outline' },
        ],
    },
    {
        key: 'nav.group.people',
        links: [
            { to: '/students', key: 'nav.students', permission: 'students.view', icon: 'mdi-account-school-outline' },
            { to: '/teachers', key: 'nav.teachers', permission: 'teachers.view', icon: 'mdi-human-male-board' },
            { to: '/classes', key: 'nav.classes', permission: 'classes.view', icon: 'mdi-google-classroom' },
        ],
    },
    {
        key: 'nav.group.money',
        links: [
            { to: '/payments', key: 'nav.payments', permission: 'payments.view', icon: 'mdi-cash-multiple' },
            { to: '/expenses', key: 'nav.expenses', permission: 'expenses.view', icon: 'mdi-receipt-text-outline' },
            { to: '/accounts', key: 'nav.accounts', permission: 'accounts.view', icon: 'mdi-bank-outline' },
            { to: '/reports', key: 'nav.reports', permission: 'reports.view', icon: 'mdi-chart-box-outline' },
        ],
    },
    {
        key: 'nav.group.system',
        links: [
            { to: '/users', key: 'nav.users', any: ['users.manage', 'roles.manage'], icon: 'mdi-account-key-outline' },
            { to: '/settings', key: 'nav.settings', permission: 'settings.manage', icon: 'mdi-cog-outline' },
        ],
    },
];

export const navGroups = groups;

export const navLinks = groups.flatMap((group) => group.links);

export function linkAllowed(link, can) {
    if (link.any) {
        return link.any.some((permission) => can(permission));
    }

    if (link.permission) {
        return can(link.permission);
    }

    return true;
}
