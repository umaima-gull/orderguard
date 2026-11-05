<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudRule extends Model
{
    protected $guarded = [];
    protected $casts = ['parameters' => 'array', 'is_active' => 'boolean'];
}