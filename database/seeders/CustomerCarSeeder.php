<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CustomerCarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $mechanicRole = Role::findByName(RoleType::MECHANIC->value, 'api');
        $customerRole = Role::findByName(RoleType::CUSTOMER->value, 'api');

        // --- SEEDING MECHANICS ---
        $this->command->info("Seeding Mechanics...");
        for ($i = 1; $i <= 5; $i++) {
            $mechanic = User::create([
                'name' => "Mechanic {$i}",
                'email' => "mechanic{$i}@example.com",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            if ($mechanicRole) {
                $mechanic->syncRoles([$mechanicRole]);
            }

            $this->command->info("Created mechanic: {$mechanic->email}");
        }

        // --- SEEDING CUSTOMERS & CARS ---
        $this->command->info("Seeding Customers and Cars...");
        $totalCars = 0;

        for ($i = 1; $i <= 10; $i++) {
            $customer = User::create([
                'name' => "Customer {$i}",
                'email' => "customer{$i}@example.com",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            if ($customerRole) {
                $customer->syncRoles([$customerRole]);
            }

            // Create 1-3 cars for each customer
            $numCars = rand(1, 3);
            for ($j = 1; $j <= $numCars; $j++) {
                Car::factory()->create([
                    'owner_id' => $customer->id,
                ]);
                $totalCars++;
            }

            $this->command->info("Created customer: {$customer->email} with {$numCars} car(s)");
        }

        $this->command->info('------------------------------');
        $this->command->info("Seeding completed:");
        $this->command->info("- Mechanics: 5");
        $this->command->info("- Customers: 10");
        $this->command->info("- Total Cars: {$totalCars}");
        $this->command->info('------------------------------');
    }
}
