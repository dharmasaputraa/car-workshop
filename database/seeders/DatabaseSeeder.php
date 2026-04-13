<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //  php artisan shield:seeder --generate --option=permissions_via_roles

        $this->call([
            ShieldSeeder::class,
            RoleSeeder::class,
        ]);
    }
}
