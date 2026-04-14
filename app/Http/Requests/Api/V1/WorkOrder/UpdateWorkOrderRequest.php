<?php

namespace App\Http\Requests\Api\V1\WorkOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Otorisasi lanjutan (Gate) sudah ditangani di dalam Controller,
        // jadi di sini kita cukup memastikan user sudah terautentikasi.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Menggunakan 'sometimes' agar client bisa mengirim sebagian data saja (PATCH behavior)
            'car_id'               => ['sometimes', 'required', 'string', 'uuid', 'exists:cars,id'],
            'diagnosis_notes'      => ['nullable', 'string', 'max:1000'],
            'estimated_completion' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * (Opsional) Custom messages jika validasi gagal
     */
    public function messages(): array
    {
        return [
            'car_id.exists' => 'Mobil yang dipilih tidak ditemukan di sistem.',
            'estimated_completion.after_or_equal' => 'Estimasi waktu selesai tidak boleh mundur ke masa lalu.',
        ];
    }
}
