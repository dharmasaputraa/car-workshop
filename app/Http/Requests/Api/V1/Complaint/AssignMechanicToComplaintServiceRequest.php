<?php

namespace App\Http\Requests\Api\V1\Complaint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class AssignMechanicToComplaintServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'mechanic_ids' => ['required', 'array', 'min:1'],
            'mechanic_ids.*' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'mechanic_ids.required' => 'At least one mechanic must be assigned.',
            'mechanic_ids.array' => 'Mechanic IDs must be an array.',
            'mechanic_ids.min' => 'At least one mechanic must be assigned.',
            'mechanic_ids.*.exists' => 'The selected mechanic does not exist.',
        ];
    }
}
