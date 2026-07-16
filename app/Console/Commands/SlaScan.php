<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Port of run_sla_breach_scan(): warns at last 20% of window, breaches when due_at passes. */
class SlaScan extends Command
{
    protected $signature = 'crm:sla-scan';
    protected $description = 'فحص تجاوزات SLA وإرسال التنبيهات';

    public function handle(NotificationService $notify): int
    {
        $now = Carbon::now();
        $warned = 0; $breached = 0;

        $open = DB::table('requests')
            ->whereNotIn('status', ['closed', 'rejected', 'cancelled', 'completed'])
            ->whereNull('sla_paused_at')
            ->whereNotNull('due_at')
            ->get(['id', 'request_number', 'assigned_to', 'due_at', 'created_at']);

        foreach ($open as $r) {
            $due = Carbon::parse($r->due_at);
            $created = Carbon::parse($r->created_at);
            $hour = $now->format('YmdH');

            if ($now->gte($due)) {
                $notify->notify($r->assigned_to, 'تجاوز SLA', "تجاوز الطلب {$r->request_number} مهلة الإنجاز.", $r->id, "/requests/{$r->id}", 'sla', 'high', "slabreach:{$r->id}:{$hour}", 60);
                $notify->notifyRoles(['system_admin', 'support_supervisor'], 'تجاوز SLA', "تجاوز الطلب {$r->request_number} مهلة الإنجاز.", $r->id, "/requests/{$r->id}", "slabreach:{$r->id}:{$hour}");
                $breached++;
            } else {
                $window = $created->diffInSeconds($due);
                $warnAt = $due->copy()->subSeconds((int) ($window * 0.2));
                if ($now->gte($warnAt)) {
                    $notify->notify($r->assigned_to, 'اقتراب تجاوز SLA', "الطلب {$r->request_number} يوشك على تجاوز المهلة.", $r->id, "/requests/{$r->id}", 'sla', 'high', "slawarn:{$r->id}:{$hour}", 60);
                    $warned++;
                }
            }
        }

        // archive read notifications older than 90 days
        DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', $now->copy()->subDays(90))->delete();

        $this->info("تنبيهات: تحذير {$warned}، تجاوز {$breached}.");

        return self::SUCCESS;
    }
}
