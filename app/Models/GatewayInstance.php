<?php

namespace App\Models;

use Database\Factories\GatewayInstanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'base_url', 'status', 'last_error', 'last_checked_at'])]
class GatewayInstance extends Model
{
    /** @use HasFactory<GatewayInstanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }
}
