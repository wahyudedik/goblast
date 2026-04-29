<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Seed the superadmin user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'info@konektivitas.com'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('Wahyu123456789@'),
                'role' => 'superadmin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
