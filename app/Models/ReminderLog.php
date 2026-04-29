<?php

namespace App\Models;

use Database\Factories\ReminderLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reminder_id', 'recipient', 'condition_key', 'sent_at'])]
class ReminderLog extends Model
{
    /** @use HasFactory<ReminderLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }
}
