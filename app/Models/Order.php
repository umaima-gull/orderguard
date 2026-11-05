<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];
    protected $casts = [
        'placed_at' => 'datetime', 'approved_at' => 'datetime', 'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime', 'cancelled_at' => 'datetime',
        'is_address_complete' => 'boolean',
    ];

    public function store()      { return $this->belongsTo(Store::class); }
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function items()      { return $this->hasMany(OrderItem::class); }
    public function shipments()  { return $this->hasMany(Shipment::class); }
    public function riskScores() { return $this->hasMany(OrderRiskScore::class); }
    public function latestRisk() { return $this->hasOne(OrderRiskScore::class)->where('is_latest', 1); }
}