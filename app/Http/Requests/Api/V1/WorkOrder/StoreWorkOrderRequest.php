<?php

namespace App\Http\Requests\Api\V1\WorkOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxDateTime = now()->addDays(7)->format('Y-m-d H:i:s');

        return [
            'car_id' => ['required', 'string', 'uuid', 'exists:cars,id'],
            'diagnosis_notes' => ['nullable', 'string', 'max:1000'],
            'estimated_completion' => [
                'nullable',
                'date',
                'after_or_equal:now',
                "before_or_equal:$maxDateTime",
            ],
        ];
    }
}
