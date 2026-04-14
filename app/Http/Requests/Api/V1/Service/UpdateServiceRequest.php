<?php

namespace App\Http\Requests\Api\V1\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'max:255', 'unique:services,name,' . $this->route('service')],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price'  => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active'   => ['boolean'],
        ];
    }
}
