<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'superadmin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'unique:plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'message_quota' => ['nullable', 'integer', 'min:0'],
            'max_devices' => ['required', 'integer', 'min:1'],
            'has_reminder' => ['boolean'],
            'has_api' => ['boolean'],
            'has_multi_device' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama paket',
            'slug' => 'slug',
            'price' => 'harga',
            'message_quota' => 'kuota pesan',
            'max_devices' => 'maksimal device',
            'has_reminder' => 'fitur reminder',
            'has_api' => 'fitur API',
            'has_multi_device' => 'fitur multi device',
            'description' => 'deskripsi',
            'sort_order' => 'urutan tampil',
        ];
    }
}
