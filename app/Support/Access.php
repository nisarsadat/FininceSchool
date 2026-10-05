<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;

class Access
{
    public static function definitions(): array
    {
        return [
            ['slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['slug' => 'students.view', 'group' => 'students'],
            ['slug' => 'students.manage', 'group' => 'students'],
            ['slug' => 'teachers.view', 'group' => 'teachers'],
            ['slug' => 'teachers.manage', 'group' => 'teachers'],
            ['slug' => 'classes.view', 'group' => 'classes'],
            ['slug' => 'classes.manage', 'group' => 'classes'],
            ['slug' => 'payments.view', 'group' => 'payments'],
            ['slug' => 'payments.manage', 'group' => 'payments'],
            ['slug' => 'expenses.view', 'group' => 'expenses'],
            ['slug' => 'expenses.manage', 'group' => 'expenses'],
            ['slug' => 'accounts.view', 'group' => 'accounts'],
            ['slug' => 'accounts.manage', 'group' => 'accounts'],
            ['slug' => 'reports.view', 'group' => 'reports'],
            ['slug' => 'settings.manage', 'group' => 'settings'],
            ['slug' => 'users.manage', 'group' => 'users'],
            ['slug' => 'roles.manage', 'group' => 'roles'],
        ];
    }

    public static function roleMap(): array
    {
        $all = array_column(self::definitions(), 'slug');
        $view = array_values(array_filter($all, fn (string $slug) => str_ends_with($slug, '.view')));
        $finance = array_values(array_filter(
            $all,
            fn (string $slug) => ! in_array($slug, ['users.manage', 'roles.manage', 'settings.manage'], true)
        ));

        return [
            'admin' => ['name' => 'Administrator', 'permissions' => $all],
            'accountant' => ['name' => 'Accountant', 'permissions' => $finance],
            'viewer' => ['name' => 'Viewer', 'permissions' => $view],
        ];
    }

    public static function sync(): void
    {
        foreach (self::definitions() as $row) {
            Permission::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }

        foreach (self::roleMap() as $slug => $role) {
            $model = Role::query()->updateOrCreate(['slug' => $slug], ['name' => $role['name']]);
            $ids = Permission::query()->whereIn('slug', $role['permissions'])->pluck('id');

            if ($slug === 'admin' || $model->wasRecentlyCreated) {
                $model->permissions()->sync($ids);
            }
        }
    }
}
