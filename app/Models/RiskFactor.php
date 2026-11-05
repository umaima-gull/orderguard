<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskFactor extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function riskScore() { return $this->belongsTo(OrderRiskScore::class, 'risk_score_id'); }
    public function rule()      { return $this->belongsTo(FraudRule::class, 'rule_id'); }
}