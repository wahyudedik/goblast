<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed system configurations first
        $this->call([
            SystemConfigSeeder::class,
        ]);

        // Seed superadmin user
        $this->call([
            SuperadminSeeder::class,
        ]);

        // Seed plans
        $this->call([
            PlanSeeder::class,
        ]);

        // Seed demo tenant with users, devices, templates, etc.
        $this->call([
            DemoTenantSeeder::class,
        ]);

        // Seed sample notifications
        $this->call([
            NotificationSeeder::class,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->newLine();
        $this->command->info('Superadmin Credentials:');
        $this->command->info('Email: info@konektivitas.com');
        $this->command->info('Password: Wahyu123456789@');
        $this->command->newLine();
        $this->command->info('Demo Tenant Credentials:');
        $this->command->info('Email: admin@demo.test');
        $this->command->info('Password: password');
        $this->command->newLine();
    }
}
