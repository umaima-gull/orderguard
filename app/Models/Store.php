<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $guarded = [];
    protected $hidden = ['access_token', 'api_key', 'api_secret', 'webhook_secret'];
    protected $casts = ['settings' => 'array', 'last_synced_at' => 'datetime'];

    public function orders()     { return $this->hasMany(Order::class); }
    public function customers()  { return $this->hasMany(Customer::class); }
    public function products()   { return $this->hasMany(Product::class); }
    public function shipments()  { return $this->hasMany(Shipment::class); }
    public function recoveryCases() { return $this->hasMany(RecoveryCase::class); }
    public function fraudRules() { return $this->hasMany(FraudRule::class); }
}