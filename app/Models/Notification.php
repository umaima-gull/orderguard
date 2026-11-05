<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];
    protected $casts = ['sent_at' => 'datetime', 'read_at' => 'datetime'];

    /** Logged-in user's in-app notifications. */
    public function scopeMine($query)
    {
        return $query->where('user_id', auth()->id())
            ->where('recipient_type', 'merchant')
            ->where('channel', 'in_app');
    }

    public function targetUrl(): string
    {
        if ($this->recovery_case_id) return route('rto-recovery.show', $this->recovery_case_id);
        if ($this->order_id)         return route('orders.show', $this->order_id);

        return route('dashboard');
    }
}