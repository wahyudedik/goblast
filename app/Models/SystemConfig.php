<?php

namespace App\Models;

use Database\Factories\SystemConfigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'type', 'description', 'updated_by', 'updated_at'])]
class SystemConfig extends Model
{
    /** @use HasFactory<SystemConfigFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
