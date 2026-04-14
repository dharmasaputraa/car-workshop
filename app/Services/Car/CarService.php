<?php

namespace App\Services\Car;

use App\DTOs\Car\StoreCarData;
use App\DTOs\Car\UpdateCarData;
use App\Models\Car;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CarService
{
    // Pattern: [Kode Wilayah][Spasi][Angka 1–4 digit][Spasi][Huruf 1–3]
    // Examples: B 1234 ABC, AB 1 X, DK 999 ZZ
    private const PLATE_NUMBER_REGEX = '/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/';

    public function __construct(
        protected CarRepositoryInterface $carRepository
    ) {}

    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Validate and normalize plate number format
     *
     * @throws \InvalidArgumentException
     */
    private function validateAndNormalizePlateNumber(string $plateNumber): string
    {
        // Normalize: uppercase and trim
        $normalized = strtoupper(trim($plateNumber));

        // Validate format
        if (!preg_match(self::PLATE_NUMBER_REGEX, $normalized)) {
            throw new \InvalidArgumentException(
                'Format plat nomor tidak valid. Format yang benar: [Kode Wilayah][Spasi][Angka 1–4 digit][Spasi][Huruf 1–3]. Contoh: B 1234 ABC'
            );
        }

        return $normalized;
    }

    /**
     * Check if plate number is unique (excluding current car if updating)
     */
    private function ensurePlateNumberIsUnique(string $plateNumber, ?string $excludeCarId = null): void
    {
        $existingCar = \App\Models\Car::where('plate_number', $plateNumber);

        if ($excludeCarId !== null) {
            $existingCar->where('id', '!=', $excludeCarId);
        }

        if ($existingCar->exists()) {
            throw new \InvalidArgumentException('Plat nomor sudah terdaftar di sistem.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getPaginatedCars(): LengthAwarePaginator
    {
        return $this->carRepository->getPaginatedCars();
    }

    public function getCarById(string $id): Car
    {
        return $this->carRepository->findById($id);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function createCar(StoreCarData $data): Car
    {
        return DB::transaction(function () use ($data) {
            // Validate and normalize plate number
            $normalizedPlateNumber = $this->validateAndNormalizePlateNumber($data->plateNumber);

            // Check uniqueness
            $this->ensurePlateNumberIsUnique($normalizedPlateNumber);

            return $this->carRepository->create([
                'owner_id'     => $data->ownerId,
                'plate_number' => $normalizedPlateNumber,
                'brand'        => $data->brand,
                'model'        => $data->model,
                'year'         => $data->year,
                'color'        => $data->color,
            ]);
        });
    }

    public function updateCar(Car $car, UpdateCarData $data): Car
    {
        return DB::transaction(function () use ($car, $data) {
            $updateData = $data->toArray();

            // Validate and normalize plate number if provided
            if (!empty($data->plateNumber)) {
                $normalizedPlateNumber = $this->validateAndNormalizePlateNumber($data->plateNumber);
                $updateData['plate_number'] = $normalizedPlateNumber;

                // Check uniqueness (exclude current car)
                $this->ensurePlateNumberIsUnique($normalizedPlateNumber, $car->id);
            }

            return $this->carRepository->update($car, $updateData);
        });
    }

    public function deleteCar(Car $car): void
    {
        $this->carRepository->delete($car);
    }
}
