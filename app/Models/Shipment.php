<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];
    protected $casts = [
        'is_exception' => 'boolean', 'dispatched_at' => 'datetime', 'last_event_at' => 'datetime',
        'delivered_at' => 'datetime', 'returned_at' => 'datetime',
    ];

    public function order()   { return $this->belongsTo(Order::class); }
    public function courier() { return $this->belongsTo(Courier::class); }
    public function events()  { return $this->hasMany(ShipmentEvent::class); }
}