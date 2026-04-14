<?php

namespace App\Http\Requests\Api\V1\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWorkOrderServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Gate
    }

    public function rules(): array
    {
        // No body parameters needed for this endpoint
        // All validation is business logic in the Action
        return [];
    }
}
