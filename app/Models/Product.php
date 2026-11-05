<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use \App\Models\Concerns\BelongsToStore;
    protected $guarded = [];

    public function store() { return $this->belongsTo(Store::class); }
}