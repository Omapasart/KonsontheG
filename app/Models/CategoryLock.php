<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryLock extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'entry_level';

    protected $keyType = 'string';

    protected $fillable = [
        'entry_level',
        'registration_closed',
        'confirmed_capacity',
        'waiting_capacity',
    ];

    protected function casts(): array
    {
        return [
            'registration_closed' => 'boolean',
            'confirmed_capacity' => 'integer',
            'waiting_capacity' => 'integer',
        ];
    }
}
