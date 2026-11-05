<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierAccount extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];
    protected $hidden = ['credentials'];
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function courier() { return $this->belongsTo(Courier::class); }
}