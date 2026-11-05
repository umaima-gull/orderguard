<?php

namespace App\Support;

use App\Models\Store;

class CurrentStore
{
    private static array $cache = [];

    /** Logged-in user's store, or null. */
    public static function find(): ?Store
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return self::$cache[$user->id] ??= $user->stores()->first();
    }

    public static function get(): Store
    {
        return self::find() ?? abort(403, 'No store is linked to this account.');
    }

    public static function id(): ?int
    {
        return self::find()?->id;
    }
}