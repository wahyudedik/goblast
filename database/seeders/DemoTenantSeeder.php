<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'email' => 'demo@waautomation.test',
            'phone' => '628123456789',
            'status' => 'active',
        ]);

        // Create admin user
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Admin',
            'email' => 'admin@demo.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create member users
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Member',
            'email' => 'member@demo.test',
            'password' => Hash::make('password'),
            'role' => 'member',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Get Business plan
        $plan = Plan::where('slug', 'business')->first();

        // Create active subscription
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'message_quota_used' => 150,
            'message_quota_limit' => null, // Unlimited for business plan
        ]);

        // Create devices
        Device::create([
            'tenant_id' => $tenant->id,
            'name' => 'WhatsApp Business 1',
            'phone_number' => '628111222333',
            'gateway_device_id' => 'device-'.Str::random(10),
            'status' => 'connected',
            'session_data' => json_encode(['connected' => true]),
            'last_seen_at' => now(),
        ]);

        Device::create([
            'tenant_id' => $tenant->id,
            'name' => 'WhatsApp Business 2',
            'phone_number' => '628111222334',
            'gateway_device_id' => 'device-'.Str::random(10),
            'status' => 'disconnected',
            'session_data' => null,
            'last_seen_at' => now()->subHours(2),
        ]);

        // Create templates
        Template::create([
            'tenant_id' => $tenant->id,
            'name' => 'Welcome Message',
            'type' => 'notification',
            'content' => "Halo {name},\n\nSelamat datang di {company}! Terima kasih telah bergabung dengan kami.\n\nSalam,\nTim {company}",
            'variables' => ['name', 'company'],
        ]);

        Template::create([
            'tenant_id' => $tenant->id,
            'name' => 'Promo Diskon',
            'type' => 'promo',
            'content' => "🎉 PROMO SPESIAL! 🎉\n\nHai {name},\n\nDapatkan diskon {discount}% untuk semua produk!\nGunakan kode: {code}\n\nBerlaku sampai {valid_until}",
            'variables' => ['name', 'discount', 'code', 'valid_until'],
        ]);

        Template::create([
            'tenant_id' => $tenant->id,
            'name' => 'Reminder Pembayaran',
            'type' => 'reminder',
            'content' => "Halo {name},\n\nIni adalah pengingat bahwa pembayaran Anda sebesar Rp {amount} akan jatuh tempo pada {due_date}.\n\nMohon segera lakukan pembayaran.\n\nTerima kasih.",
            'variables' => ['name', 'amount', 'due_date'],
        ]);
    }
}
