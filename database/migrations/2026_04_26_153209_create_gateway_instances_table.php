<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_instances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url', 500);
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->text('last_error')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_instances');
    }
};
