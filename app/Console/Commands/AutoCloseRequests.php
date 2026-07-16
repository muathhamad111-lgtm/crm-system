<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Port of auto_close_unresponsive_requests(): closes stale awaiting-customer / completed requests. */
class AutoCloseRequests extends Command
{
    protected $signature = 'crm:auto-close';
    protected $description = 'إغلاق الطلبات غير المستجاب لها تلقائيًا حسب مهلة الإغلاق';

    public function handle(NotificationService $notify): int
    {
        $now = Carbon::now();
        $closed = 0;

        $rows = DB::table('requests')
            ->whereIn('status', ['awaiting_customer', 'completed'])
            ->whereNotNull('auto_close_due_at')
            ->where('auto_close_due_at', '<=', $now)
            ->get(['id', 'request_number', 'status', 'customer_id', 'assigned_to']);

        foreach ($rows as $r) {
            $reason = $r->status === 'completed' ? 'auto_closed_after_completion' : 'auto_closed';
            DB::table('requests')->where('id', $r->id)->update([
                'status' => 'closed', 'progress' => 100, 'closed_at' => $now,
                'closure_channel' => 'auto', 'closure_reason_code' => $reason,
                'auto_close_due_at' => null, 'updated_at' => $now,
            ]);
            DB::table('request_activity_log')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(), 'request_id' => $r->id,
                'action' => $reason, 'created_at' => $now,
            ]);
            $notify->notify($r->customer_id, 'تم إغلاق طلبك', "تم إغلاق الطلب {$r->request_number} تلقائيًا.", $r->id, "/requests/{$r->id}", 'request');
            $closed++;
        }

        $this->info("أُغلق {$closed} طلبًا.");

        return self::SUCCESS;
    }
}
