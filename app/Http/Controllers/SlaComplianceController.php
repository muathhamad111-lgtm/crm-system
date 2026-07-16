<?php

namespace App\Http\Controllers;

use App\Models\Request as CrmRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SlaComplianceController extends Controller
{
    /** Compliance target percentage (green if met). */
    private const TARGET = 80;

    /**
     * SLA compliance dashboard.
     *
     * Compliance is measured two ways, both kept pragmatic and correct:
     *  1) request_stage_sla_log — breached vs. not breached stage records.
     *  2) requests — resolution SLA: closed_at <= due_at (met) vs. breached / overdue.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        // ---- Resolution compliance from requests (due_at vs closed_at) ----
        $reqScope = fn () => CrmRequest::query()
            ->when(! $isStaff, fn ($q) => $q->where('customer_id', $user->id))
            ->whereNotNull('due_at');

        $rows = $reqScope()
            ->get(['id', 'category_id', 'priority', 'status', 'due_at', 'closed_at'])
            ->map(function ($r) {
                $due = $r->due_at;
                $closed = $r->closed_at;
                if ($closed) {
                    $r->outcome = $closed->lessThanOrEqualTo($due) ? 'met' : 'breached';
                } elseif ($due->isPast()) {
                    $r->outcome = 'overdue';
                } else {
                    $r->outcome = 'pending';
                }

                return $r;
            });

        // Overall counts. "Decided" = met + breached + overdue (overdue counts against compliance).
        $met = $rows->where('outcome', 'met')->count();
        $breached = $rows->where('outcome', 'breached')->count();
        $overdue = $rows->where('outcome', 'overdue')->count();
        $pending = $rows->where('outcome', 'pending')->count();
        $decided = $met + $breached + $overdue;
        $overallPct = $decided > 0 ? (int) round(($met / $decided) * 100) : null;

        // ---- Breakdown by priority ----
        $priorityOrder = ['urgent', 'high', 'medium', 'low'];
        $priorityLabels = ['urgent' => 'عاجل', 'high' => 'مرتفع', 'medium' => 'متوسط', 'low' => 'منخفض'];
        $byPriority = collect($priorityOrder)->map(function ($p) use ($rows, $priorityLabels) {
            $g = $rows->where('priority', $p);
            return $this->bucket($p, $priorityLabels[$p], $g);
        })->values();

        // ---- Breakdown by category ----
        $categoryNames = DB::table('categories')->pluck('name_ar', 'id');
        $byCategory = $rows->groupBy('category_id')->map(function ($g, $catId) use ($categoryNames) {
            return $this->bucket($catId, $categoryNames[$catId] ?? 'غير مصنّف', $g);
        })->sortByDesc('total')->values();

        // ---- Stage-level view from request_stage_sla_log (breached vs not) ----
        $stageScope = DB::table('request_stage_sla_log as l');
        if (! $isStaff) {
            $stageScope->join('requests as r', 'r.id', '=', 'l.request_id')
                ->where('r.customer_id', $user->id);
        }
        $stageTotal = (clone $stageScope)->whereNotNull('l.ended_at')->count();
        $stageBreached = (clone $stageScope)->where('l.breached', true)->count();
        $stageMet = max($stageTotal - $stageBreached, 0);
        $stagePct = $stageTotal > 0 ? (int) round(($stageMet / $stageTotal) * 100) : null;

        return Inertia::render('SlaCompliance/Index', [
            'target' => self::TARGET,
            'overall' => [
                'pct' => $overallPct,
                'met' => $met,
                'breached' => $breached,
                'overdue' => $overdue,
                'pending' => $pending,
                'decided' => $decided,
                'total' => $rows->count(),
            ],
            'stage' => [
                'pct' => $stagePct,
                'met' => $stageMet,
                'breached' => $stageBreached,
                'total' => $stageTotal,
            ],
            'byPriority' => $byPriority,
            'byCategory' => $byCategory,
        ]);
    }

    /** Build a compliance bucket for a group of request rows. */
    private function bucket(string $key, string $label, $group): array
    {
        $met = $group->where('outcome', 'met')->count();
        $breached = $group->where('outcome', 'breached')->count();
        $overdue = $group->where('outcome', 'overdue')->count();
        $pending = $group->where('outcome', 'pending')->count();
        $decided = $met + $breached + $overdue;

        return [
            'key' => $key,
            'label' => $label,
            'pct' => $decided > 0 ? (int) round(($met / $decided) * 100) : null,
            'met' => $met,
            'breached' => $breached,
            'overdue' => $overdue,
            'pending' => $pending,
            'total' => $group->count(),
        ];
    }
}
