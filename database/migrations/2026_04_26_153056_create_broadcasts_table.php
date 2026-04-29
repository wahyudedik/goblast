<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null');
            $table->string('name');
            $table->enum('status', ['draft', 'queued', 'running', 'completed', 'cancelled', 'failed'])->default('draft');
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('pending_count')->default(0);
            $table->enum('source_type', ['csv', 'database']);
            $table->string('csv_path', 500)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
