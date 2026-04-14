<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RoleType::cases() as $roleEnum) {
            Role::firstOrCreate([
                'name'       => $roleEnum->value,
                'guard_name' => 'api',
            ]);
        }

        $superAdminRole = Role::where('name', RoleType::SUPER_ADMIN->value)
            ->where('guard_name', 'api')
            ->first();

        if ($superAdminRole) {
            $allPermissions = Permission::where('guard_name', 'api')->get();
            $superAdminRole->syncPermissions($allPermissions);
        }

        $users = [
            [
                'email' => 'superadmin@example.com',
                'name'  => 'Super Admin',
                'role'  => RoleType::SUPER_ADMIN->value,
            ],
            [
                'email' => 'admin@example.com',
                'name'  => 'Admin',
                'role'  => RoleType::ADMIN->value,
            ],
            [
                'email' => 'mechanic@example.com',
                'name'  => 'Mechanic',
                'role'  => RoleType::MECHANIC->value,
            ],
            [
                'email' => 'user@example.com',
                'name'  => 'User',
                'role'  => RoleType::CUSTOMER->value,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name'     => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $role = Role::where('name', $userData['role'])
                ->where('guard_name', 'api')
                ->first();

            if ($role) {
                $user->syncRoles([$role]);
            }
        }
    }
}
