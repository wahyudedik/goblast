<?php

namespace App\Models;

use Database\Factories\KeywordRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'device_id', 'keyword', 'reply', 'priority', 'is_active'])]
class KeywordRule extends Model
{
    /** @use HasFactory<KeywordRuleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
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
