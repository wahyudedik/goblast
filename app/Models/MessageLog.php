<?php

namespace App\Models;

use Database\Factories\MessageLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'device_id', 'broadcast_id', 'reminder_id', 'template_id', 'job_id', 'recipient', 'message', 'status', 'source', 'error_message', 'attempts', 'sent_at', 'failed_at'])]
class MessageLog extends Model
{
    /** @use HasFactory<MessageLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
