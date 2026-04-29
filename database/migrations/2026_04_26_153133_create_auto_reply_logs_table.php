<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('keyword_rule_id')->nullable()->constrained('keyword_rules')->onDelete('set null');
            $table->string('from', 20);
            $table->text('message');
            $table->boolean('matched')->default(false);
            $table->boolean('reply_sent')->default(false);
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('device_id');
            $table->index('from');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_logs');
    }
};
