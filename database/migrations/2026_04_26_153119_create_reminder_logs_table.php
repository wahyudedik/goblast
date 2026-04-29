<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained('reminders')->onDelete('cascade');
            $table->string('recipient', 20);
            $table->string('condition_key');
            $table->timestamp('sent_at');

            $table->index('reminder_id');
            $table->index(['reminder_id', 'recipient', 'condition_key', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
