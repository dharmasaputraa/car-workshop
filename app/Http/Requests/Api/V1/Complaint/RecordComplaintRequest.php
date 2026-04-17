<?php

namespace App\Http\Requests\Api\V1\Complaint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RecordComplaintRequest extends FormRequest
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
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'uuid', 'exists:services,id'],
            'services.*.description' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'The description must be at least 10 characters.',
            'services.min' => 'At least one service must be selected.',
            'services.*.service_id.exists' => 'One or more selected services do not exist.',
        ];
    }
}
