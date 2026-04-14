<?php

namespace App\Http\Requests\Api\V1\User\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user('api');

        return $user->can('updateProfile', $user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Hash::check($value, $this->user('api')->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
