<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->foreignId('broadcast_id')->nullable()->constrained('broadcasts')->onDelete('set null');
            $table->unsignedBigInteger('reminder_id')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null');
            $table->string('job_id', 100)->nullable();
            $table->string('recipient', 20);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled', 'retrying'])->default('pending');
            $table->enum('source', ['broadcast', 'trigger', 'reminder', 'api', 'auto_reply']);
            $table->text('error_message')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('device_id');
            $table->index('broadcast_id');
            $table->index('recipient');
            $table->index('created_at');
            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
