<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryAction extends Model
{
    public $timestamps = false;   // table mein sirf created_at hai (DB default)
    protected $guarded = [];
    protected $casts = ['details' => 'array', 'created_at' => 'datetime'];

    public function recoveryCase() { return $this->belongsTo(RecoveryCase::class, 'recovery_case_id'); }
}