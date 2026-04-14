<?php

namespace App\Http\Requests\Api\V1\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class DiagnoseWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Idealnya divalidasi hasRole('mechanic') atau 'admin'
    }

    public function rules(): array
    {
        return [
            'diagnosis_notes'        => ['nullable', 'string', 'max:2000'],
            'services'               => ['nullable', 'array'],
            'services.*.service_id'  => ['required_with:services', 'string', 'uuid', 'exists:services,id'],
            'services.*.notes'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
