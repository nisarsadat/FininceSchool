<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->with('permissions:id,slug,group')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => $this->payload($role));

        return response()->json([
            'roles' => $roles,
            'catalog' => Access::definitions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
        ]);
        $ids = Permission::query()->whereIn('slug', $data['permissions'])->pluck('id');
        $role->permissions()->sync($ids);

        return response()->json($this->payload($role), 201);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->slug === 'admin') {
            Access::sync();

            return response()->json($this->payload($role->fresh('permissions')));
        }

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ]);

        $ids = Permission::query()->whereIn('slug', $data['permissions'])->pluck('id');
        $role->permissions()->sync($ids);

        return response()->json($this->payload($role->fresh('permissions')));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '' || in_array($base, ['admin', 'accountant', 'viewer'], true)) {
            $base = 'role';
        }

        $slug = $base;
        $n = 2;

        while (Role::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    private function payload(Role $role): array
    {
        $role->loadMissing('permissions:id,slug,group');

        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions' => $role->slug === 'admin'
                ? array_column(Access::definitions(), 'slug')
                : $role->permissions->pluck('slug')->values(),
        ];
    }
}
