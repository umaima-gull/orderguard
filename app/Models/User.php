<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
   use Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'phone', 'avatar_url', 'role', 'status'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_user')->withPivot('role');
    }

    public function initials(): string
    {
        $words = array_filter(explode(' ', trim($this->name)));

        return strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
    }
}