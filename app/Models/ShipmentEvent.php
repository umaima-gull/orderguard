<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'is_exception' => 'boolean', 'event_time' => 'datetime'];

    public function shipment() { return $this->belongsTo(Shipment::class); }
}