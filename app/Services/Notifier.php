<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\DB;

class Notifier
{
    public static function merchant(Store $store, string $subject, string $body, ?int $orderId = null, ?int $caseId = null): void
    {
        $userIds = DB::table('store_user')->where('store_id', $store->id)->pluck('user_id')->all();
        if (!$userIds) {
            $userIds = [$store->owner_id];
        }

        $rows = [];
        foreach (array_unique($userIds) as $uid) {
            $rows[] = [
                'store_id' => $store->id, 'recipient_type' => 'merchant', 'user_id' => $uid,
                'order_id' => $orderId, 'recovery_case_id' => $caseId, 'channel' => 'in_app',
                'subject' => substr($subject, 0, 200), 'body' => $body, 'status' => 'sent',
                'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ];
        }
        DB::table('notifications')->insert($rows);
    }
}