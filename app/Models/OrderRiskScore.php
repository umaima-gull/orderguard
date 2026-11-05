<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRiskScore extends Model
{
    public $timestamps = false;   // table mein sirf created_at hai
    protected $guarded = [];
    protected $casts = ['is_latest' => 'boolean', 'evaluated_at' => 'datetime'];

    public function order()   { return $this->belongsTo(Order::class); }
    public function factors() { return $this->hasMany(RiskFactor::class, 'risk_score_id'); }
}