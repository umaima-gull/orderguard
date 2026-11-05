<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationLog extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];
    protected $hidden = ['otp_hash', 'token_hash'];
    protected $casts = ['sent_at' => 'datetime', 'responded_at' => 'datetime', 'expires_at' => 'datetime'];

    public function order()    { return $this->belongsTo(Order::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}