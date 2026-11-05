<?php

namespace App\Support;

class Permissions
{
    // role => levels allowed. Levels: view < act < manage < admin
    private const LEVELS = ['viewer' => 1, 'agent' => 2, 'manager' => 3, 'owner' => 4];

    public static function role(): string
    {
        $store = CurrentStore::find();
        if (!$store || !auth()->check()) {
            return 'viewer';
        }

        return \Illuminate\Support\Facades\DB::table('store_user')
            ->where('store_id', $store->id)->where('user_id', auth()->id())->value('role') ?? 'viewer';
    }

    public static function can(string $level): bool
    {
        $need = ['view' => 1, 'act' => 2, 'manage' => 3, 'admin' => 4][$level] ?? 4;

        return (self::LEVELS[self::role()] ?? 0) >= $need;
    }
}