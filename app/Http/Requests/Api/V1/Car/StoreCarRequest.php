<?php

namespace App\Http\Requests\Api\V1\Car;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id'     => ['required', 'uuid', 'exists:users,id'],
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/i', 'unique:cars,plate_number'],
            'brand'        => ['required', 'string', 'max:100'],
            'model'        => ['required', 'string', 'max:100'],
            'year'         => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color'        => ['required', 'string', 'max:50'],
        ];
    }
}
