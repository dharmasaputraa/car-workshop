<?php

namespace App\Http\Requests\Api\V1\Mechanic;

use Illuminate\Foundation\Http\FormRequest;

class StoreMechanicAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_order_service_id' => ['required', 'uuid', 'exists:work_order_services,id'],
            // Pastikan user yang di-assign benar-benar ada
            'mechanic_id'           => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}
