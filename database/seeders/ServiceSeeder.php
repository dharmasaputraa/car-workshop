<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create some core services that are always available
        $coreServices = [
            [
                'name' => 'Standard Engine Oil Change',
                'description' => 'Standard engine oil replacement and checking the drain plug washer.',
                'base_price' => 50000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Basic Tune Up',
                'description' => 'Throttle body cleaning, spark plug check, and air filter check.',
                'base_price' => 250000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Wheel Alignment & Balancing',
                'description' => 'Wheel alignment and tire balancing.',
                'base_price' => 175000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Front Brake Pad Replacement',
                'description' => 'Front disc brake pad replacement and installation.',
                'base_price' => 100000.00,
                'is_active' => true,
            ],
        ];

        $totalCore = 0;
        foreach ($coreServices as $serviceData) {
            Service::create($serviceData);
            $totalCore++;
            $this->command->info("Created core service: {$serviceData['name']}");
        }

        // 2. Create 10 additional random services using the Factory
        $randomServicesCount = 10;
        Service::factory()->count($randomServicesCount)->create();
        $this->command->info("Created {$randomServicesCount} random services via factory.");

        // 3. Create 2 inactive services for testing purposes
        Service::factory()->count(2)->inactive()->create();
        $this->command->info("Created 2 inactive services.");

        // Summary Output
        $totalServices = $totalCore + $randomServicesCount + 2;
        $this->command->info('------------------------------');
        $this->command->info("Service Seeding completed:");
        $this->command->info("- Core Services: {$totalCore}");
        $this->command->info("- Random Services: {$randomServicesCount}");
        $this->command->info("- Inactive Services: 2");
        $this->command->info("- Total Services: {$totalServices}");
        $this->command->info('------------------------------');
    }
}
