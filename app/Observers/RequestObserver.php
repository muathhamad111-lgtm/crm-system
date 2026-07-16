<?php

namespace App\Observers;

use App\Models\Request as CrmRequest;
use App\Services\Sla\SlaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RequestObserver
{
    /** Set SLA deadlines from the category config when a request is created. */
    public function creating(CrmRequest $request): void
    {
        if ($request->due_at && $request->response_due_at) {
            return;
        }

        $category = $request->category_id
            ? DB::table('categories')->where('id', $request->category_id)->first()
            : null;

        if (! $category) {
            return;
        }

        $sla = new SlaService;
        $now = Carbon::now();

        if (! $request->due_at && ! empty($category->sla_hours)) {
            $request->due_at = $sla->computeDeadline($now, (int) $category->sla_hours * 60);
        }
        if (! $request->response_due_at && ! empty($category->response_sla_hours)) {
            $request->response_due_at = $sla->computeDeadline($now, (int) $category->response_sla_hours * 60);
        }
        if (empty($request->escalation_at) && ! empty($category->escalation_hours)) {
            $request->escalation_at = $sla->computeDeadline($now, (int) $category->escalation_hours * 60);
        }
        if (empty($request->auto_close_due_at) && ! empty($category->auto_close_days)) {
            $request->auto_close_due_at = $now->copy()->addDays((int) $category->auto_close_days);
        }
    }
}
