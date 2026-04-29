<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_cooldowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('keyword_rule_id')->constrained('keyword_rules')->onDelete('cascade');
            $table->string('from', 20);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['device_id', 'keyword_rule_id', 'from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_cooldowns');
    }
};
