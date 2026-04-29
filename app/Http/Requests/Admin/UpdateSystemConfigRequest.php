<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemConfigRequest extends FormRequest
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
        $config = $this->route('system_config');
        $type = $config?->type ?? 'string';

        return match ($type) {
            'integer' => [
                'value' => ['required', 'integer', ...$this->integerRangeRules($config?->key)],
            ],
            'boolean' => [
                'value' => ['required', 'in:true,false,0,1'],
            ],
            'json' => [
                'value' => ['required', 'json'],
            ],
            default => [
                'value' => ['required', 'string', 'max:1000'],
            ],
        };
    }

    /**
     * Get integer range validation rules based on the config key.
     *
     * @return array<string>
     */
    private function integerRangeRules(?string $key): array
    {
        return match ($key) {
            'default_rate_limit_per_hour' => ['min:1', 'max:1000'],
            'default_delay_min_seconds' => ['min:1', 'max:60'],
            'default_delay_max_seconds' => ['min:1', 'max:60'],
            'trial_duration_days' => ['min:1', 'max:365'],
            'log_retention_days' => ['min:1', 'max:365'],
            'system_log_retention_days' => ['min:1', 'max:730'],
            'max_csv_file_size_mb' => ['min:1', 'max:50'],
            'device_health_check_interval_seconds' => ['min:10', 'max:3600'],
            'gateway_timeout_seconds' => ['min:5', 'max:120'],
            default => ['min:0'],
        };
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'value' => 'nilai konfigurasi',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.in' => 'Nilai harus berupa true atau false.',
            'value.json' => 'Nilai harus berupa JSON yang valid.',
        ];
    }
}
