<?php

namespace App\Http\Requests\Api\V1\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class AssignMechanicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Pastikan ID yang dikirim benar-benar milik user dengan role mechanic
            'mechanic_id' => ['required', 'string', 'uuid', 'exists:users,id'],
        ];
    }
}
