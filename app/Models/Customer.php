<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];
    protected $casts = ['is_blacklisted' => 'boolean', 'first_order_at' => 'datetime', 'last_order_at' => 'datetime'];

    public function store()  { return $this->belongsTo(Store::class); }
    public function orders() { return $this->hasMany(Order::class); }
}