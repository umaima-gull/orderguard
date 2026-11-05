<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryCase extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];
    protected $hidden = ['recovery_token_hash'];
    protected $casts = [
        'opened_at' => 'datetime', 'first_contact_at' => 'datetime',
        'due_at' => 'datetime', 'resolved_at' => 'datetime', 'reschedule_date' => 'date','recovery_token_expires_at' => 'datetime',
    ];

    public function order()    { return $this->belongsTo(Order::class); }
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function actions() { return $this->hasMany(RecoveryAction::class, 'recovery_case_id')->orderByDesc('created_at')->orderByDesc('id'); }
}