<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('group', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone_number']);
            $table->index('phone_number');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
