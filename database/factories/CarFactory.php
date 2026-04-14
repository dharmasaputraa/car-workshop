<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = User::factory()->create();

        $brands = [
            'Toyota',
            'Honda',
            'BMW',
            'Mercedes-Benz',
            'Audi',
            'Volkswagen',
            'Ford',
            'Chevrolet',
            'Nissan',
            'Mazda',
            'Hyundai',
            'Kia',
            'Subaru',
            'Mitsubishi',
            'Volvo',
            'Jaguar',
            'Land Rover',
            'Porsche'
        ];

        $models = [
            'Camry',
            'Civic',
            '3 Series',
            'C-Class',
            'A4',
            'Golf',
            'Mustang',
            'Camaro',
            'Altima',
            'Mazda3',
            'Elantra',
            'Forte',
            'Impreza',
            'Lancer',
            'XC60',
            'XE',
            'Range Rover',
            '911'
        ];

        $colors = [
            'Black',
            'White',
            'Silver',
            'Gray',
            'Red',
            'Blue',
            'Green',
            'Yellow',
            'Orange',
            'Brown',
            'Beige',
            'Gold'
        ];

        $brand = $this->faker->randomElement($brands);

        return [
            'owner_id' => $owner->id,
            'plate_number' => strtoupper($this->faker->bothify('?? #### ??')),
            'brand' => $brand,
            'model' => $this->faker->randomElement($models),
            'year' => $this->faker->numberBetween(2010, 2025),
            'color' => $this->faker->randomElement($colors),
        ];
    }
}
