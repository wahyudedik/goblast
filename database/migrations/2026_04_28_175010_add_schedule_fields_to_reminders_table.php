<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('frequency', 20)->default('daily')->after('type');
            $table->string('send_time', 5)->default('08:00')->after('frequency');
            $table->unsignedTinyInteger('send_day')->nullable()->after('send_time');
            $table->text('recipients')->nullable()->after('send_day');
            $table->text('message')->nullable()->after('recipients');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'send_time', 'send_day', 'recipients', 'message']);
        });
    }
};
