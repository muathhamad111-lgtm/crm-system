<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Port of auto_escalate_overdue(): bumps priority one level and marks escalated. */
class AutoEscalateOverdue extends Command
{
    protected $signature = 'crm:auto-escalate';
    protected $description = 'تصعيد الطلبات المتجاوزة تلقائيًا';

    private array $bump = ['low' => 'medium', 'medium' => 'high', 'high' => 'urgent', 'urgent' => 'urgent'];

    public function handle(NotificationService $notify): int
    {
        $now = Carbon::now();
        $n = 0;

        $rows = DB::table('requests')
            ->whereNotNull('escalation_at')->where('escalation_at', '<=', $now)
            ->whereNull('escalated_at')
            ->whereNotIn('status', ['closed', 'rejected', 'cancelled', 'completed'])
            ->get(['id', 'request_number', 'priority', 'assigned_to']);

        foreach ($rows as $r) {
            $prio = $r->priority instanceof \BackedEnum ? $r->priority->value : $r->priority;
            DB::table('requests')->where('id', $r->id)->update([
                'escalated_at' => $now, 'escalation_level' => DB::raw('escalation_level + 1'),
                'priority' => $this->bump[$prio] ?? $prio,
                'status' => 'escalated', 'updated_at' => $now,
            ]);
            DB::table('request_activity_log')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(), 'request_id' => $r->id,
                'action' => 'auto_escalation', 'created_at' => $now,
            ]);
            $notify->notifyRoles(['system_admin', 'support_supervisor'], 'تصعيد تلقائي', "تم تصعيد الطلب {$r->request_number}.", $r->id, "/requests/{$r->id}");
            $n++;
        }

        $this->info("صُعّد {$n} طلبًا.");

        return self::SUCCESS;
    }
}
