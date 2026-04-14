<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerCarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 mechanics
        $mechanics = [];
        for ($i = 1; $i <= 5; $i++) {
            $mechanic = User::create([
                'name' => "Mechanic {$i}",
                'email' => "mechanic{$i}@example.com",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $mechanic->assignRole(RoleType::MECHANIC->value);
            $mechanics[] = $mechanic;

            $this->command->info("Created mechanic: {$mechanic->email}");
        }

        // Create 10 customers, each with 1-3 cars
        $customers = [];
        $totalCars = 0;

        for ($i = 1; $i <= 10; $i++) {
            $customer = User::create([
                'name' => "Customer {$i}",
                'email' => "customer{$i}@example.com",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $customer->assignRole(RoleType::CUSTOMER->value);
            $customers[] = $customer;

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
