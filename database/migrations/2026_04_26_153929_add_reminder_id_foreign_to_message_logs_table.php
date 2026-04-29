<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->foreign('reminder_id')->references('id')->on('reminders')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropForeign(['reminder_id']);
        });
    }
};
