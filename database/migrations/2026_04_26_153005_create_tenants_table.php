<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['active', 'suspended', 'trial', 'expired'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspended_reason')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
