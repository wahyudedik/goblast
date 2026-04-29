<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendBulkRequest extends FormRequest
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
        return [
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'recipients' => ['required', 'array', 'min:1', 'max:10000'],
            'recipients.*' => ['required', 'string', 'regex:/^\+?\d{10,15}$/'],
            'message' => ['required_without:template_id', 'nullable', 'string', 'max:4096'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipients.required' => 'Daftar nomor tujuan wajib diisi.',
            'recipients.min' => 'Minimal satu nomor tujuan harus disertakan.',
            'recipients.max' => 'Maksimal 10.000 nomor tujuan per broadcast.',
            'recipients.*.regex' => 'Format nomor telepon tidak valid. Gunakan format internasional (contoh: 6281234567890).',
            'device_id.exists' => 'Device tidak ditemukan.',
            'template_id.exists' => 'Template tidak ditemukan.',
            'message.required_without' => 'Pesan wajib diisi jika template_id tidak disertakan.',
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
            'device_id' => 'device',
            'recipients' => 'daftar nomor tujuan',
            'recipients.*' => 'nomor tujuan',
            'message' => 'pesan',
            'template_id' => 'template',
        ];
    }
}
