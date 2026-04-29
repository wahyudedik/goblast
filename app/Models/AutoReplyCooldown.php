<?php

namespace App\Models;

use Database\Factories\AutoReplyCooldownFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['device_id', 'keyword_rule_id', 'from', 'expires_at'])]
class AutoReplyCooldown extends Model
{
    /** @use HasFactory<AutoReplyCooldownFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function keywordRule(): BelongsTo
    {
        return $this->belongsTo(KeywordRule::class);
    }
}
