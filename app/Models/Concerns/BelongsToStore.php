<?php

namespace App\Models\Concerns;

use App\Support\CurrentStore;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToStore
{
    protected static function bootBelongsToStore(): void
    {
        static::addGlobalScope('store', function (Builder $query) {
            // Only when a user is logged in. Webhooks and artisan commands see everything.
            if ($storeId = CurrentStore::id()) {
                $query->where($query->getModel()->getTable() . '.store_id', $storeId);
            }
        });
    }
}