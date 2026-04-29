<?php

namespace App\Models;

use Database\Factories\AutoReplyLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'device_id', 'keyword_rule_id', 'from', 'message', 'matched', 'reply_sent', 'received_at'])]
class AutoReplyLog extends Model
{
    /** @use HasFactory<AutoReplyLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'matched' => 'boolean',
            'reply_sent' => 'boolean',
            'received_at' => 'datetime',
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

    public function keywordRule(): BelongsTo
    {
        return $this->belongsTo(KeywordRule::class);
    }
}
