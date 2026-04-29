<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Paket pemula untuk bisnis kecil yang baru memulai otomasi WhatsApp. Cocok untuk UMKM dan toko online.',
                'price' => 49000.00, // Rp 49.000/bulan
                'message_quota' => 500,
                'max_devices' => 1,
                'has_reminder' => false,
                'has_api' => false,
                'has_multi_device' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Paket profesional dengan fitur lengkap untuk bisnis berkembang. Termasuk auto-reply dan reminder otomatis.',
                'price' => 149000.00, // Rp 149.000/bulan
                'message_quota' => 2000,
                'max_devices' => 2,
                'has_reminder' => true,
                'has_api' => false,
                'has_multi_device' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Paket bisnis dengan pesan unlimited dan API integration. Ideal untuk perusahaan dengan volume tinggi.',
                'price' => 299000.00, // Rp 299.000/bulan
                'message_quota' => null, // Unlimited
                'max_devices' => 5,
                'has_reminder' => true,
                'has_api' => true,
                'has_multi_device' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Paket enterprise dengan unlimited devices dan custom solution. Termasuk dedicated support dan SLA guarantee.',
                'price' => 999000.00, // Rp 999.000/bulan
                'message_quota' => null, // Unlimited
                'max_devices' => 999, // Practically unlimited
                'has_reminder' => true,
                'has_api' => true,
                'has_multi_device' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
