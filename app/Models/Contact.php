<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'phone_number', 'name', 'email', 'group', 'notes'])]
class Contact extends Model
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get display name (name or phone number).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->phone_number;
    }
}
