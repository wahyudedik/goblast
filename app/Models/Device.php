<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'name', 'phone_number', 'gateway_device_id', 'status', 'session_data'])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'session_data' => 'encrypted',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function keywordRules(): HasMany
    {
        return $this->hasMany(KeywordRule::class);
    }

    public function autoReplyLogs(): HasMany
    {
        return $this->hasMany(AutoReplyLog::class);
    }

    public function autoReplyCooldowns(): HasMany
    {
        return $this->hasMany(AutoReplyCooldown::class);
    }
}
