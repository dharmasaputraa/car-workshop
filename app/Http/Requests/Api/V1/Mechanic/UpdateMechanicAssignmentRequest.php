<?php

namespace App\Http\Requests\Api\V1\Mechanic;

use App\Enums\MechanicAssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMechanicAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'       => ['required', Rule::enum(MechanicAssignmentStatus::class)],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
