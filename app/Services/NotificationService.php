<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Central in-app notification inserter with de-duplication —
 * port of the original Postgres notify() function.
 */
class NotificationService
{
    /** Insert a notification, skipping duplicates within $dedupWindowMinutes. */
    public function notify(
        ?string $userId,
        string $title,
        ?string $body = null,
        ?string $requestId = null,
        ?string $linkPath = null,
        string $type = 'system',
        string $priority = 'normal',
        ?string $dedupKey = null,
        int $dedupWindowMinutes = 10,
    ): void {
        if (! $userId) {
            return;
        }

        if ($dedupKey) {
            $exists = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('dedup_key', $dedupKey)
                ->where('created_at', '>=', Carbon::now()->subMinutes($dedupWindowMinutes))
                ->exists();
            if ($exists) {
                return;
            }
        }

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'scope' => 'user',
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link_path' => $linkPath,
            'priority' => $priority,
            'request_id' => $requestId,
            'dedup_key' => $dedupKey,
            'created_at' => Carbon::now(),
        ]);
    }

    /** Notify all users holding any of the given roles. */
    public function notifyRoles(array $roles, string $title, ?string $body = null, ?string $requestId = null, ?string $linkPath = null, ?string $dedupKey = null): void
    {
        $userIds = DB::table('user_roles')->whereIn('role', $roles)->pluck('user_id')->unique();
        foreach ($userIds as $uid) {
            $this->notify($uid, $title, $body, $requestId, $linkPath, 'system', 'high', $dedupKey ? $dedupKey.':'.$uid : null);
        }
    }
}
