<?php

namespace App\Http\Requests\Api\V1\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id'     => ['sometimes', 'uuid', 'exists:users,id'],
            'plate_number' => ['sometimes', 'string', 'max:20', 'regex:/^[A-Z]{1,2}\s\d{1,4}\s[A-Z]{1,3}$/i', Rule::unique('cars')->ignore($this->route('car'))],
            'brand'        => ['sometimes', 'string', 'max:100'],
            'model'        => ['sometimes', 'string', 'max:100'],
            'year'         => ['sometimes', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color'        => ['sometimes', 'string', 'max:50'],
        ];
    }
}
