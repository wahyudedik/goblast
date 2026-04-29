<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('phone_number', 20)->nullable();
            $table->string('gateway_device_id', 100)->unique();
            $table->enum('status', ['pending', 'connected', 'disconnected', 'error'])->default('pending');
            $table->timestamp('last_seen_at')->nullable();
            $table->text('session_data')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('gateway_device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
