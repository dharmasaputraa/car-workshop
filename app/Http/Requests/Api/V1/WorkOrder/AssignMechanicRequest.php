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
            'mechanic_ids' => ['required', 'array', 'min:1'],
            'mechanic_ids.*' => ['required', 'string', 'uuid', 'exists:users,id'],
        ];
    }
}
