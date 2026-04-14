<?php

namespace App\Repositories\Contracts;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServiceRepositoryInterface
{
    public function getPaginatedServices(): LengthAwarePaginator;
    public function findById(string $id): Service;
    public function create(array $data): Service;
    public function update(Service $service, array $data): Service;
    public function delete(Service $service): void;
    public function toggleActive(Service $service): Service;
}
