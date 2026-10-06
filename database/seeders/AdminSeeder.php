<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * The only rows a blank desk needs: the permission/role graph and the
     * single ADMIN sign-in (code ADMIN, password). Everything else stays
     * empty so the reset leaves a clean system, not the demo dataset.
     */
    public function run(): void
    {
        Access::sync();

        $adminRole = Role::query()->where('slug', 'admin')->first();

        User::query()->firstOrCreate(
            ['code' => 'ADMIN'],
            [
                'name' => 'School Admin',
                'email' => 'admin@edufinance.pro',
                'password' => 'password',
                'role_id' => $adminRole?->id,
                'is_active' => true,
            ]
        );
    }
}
