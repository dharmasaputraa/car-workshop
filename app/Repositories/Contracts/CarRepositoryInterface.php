<?php

namespace App\Repositories\Contracts;

use App\Models\Car;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CarRepositoryInterface
{
    public function getPaginatedCars(): LengthAwarePaginator;
    public function findById(string $id): Car;
    public function create(array $data): Car;
    public function update(Car $car, array $data): Car;
    public function delete(Car $car): void;
}
