<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->onDelete('set null');
            $table->enum('role', ['superadmin', 'admin', 'member'])->default('member')->after('email');
            $table->boolean('is_active')->default(true)->after('role');

            $table->index('tenant_id');
            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor('tenants');
            $table->dropColumn(['tenant_id', 'role', 'is_active']);
        });
    }
};
