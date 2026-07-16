<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    /** BI operations dashboard — stat tiles with breakdowns. */
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $team = $request->query('team');

        // Base request scope honouring the (optional) filters.
        $scope = fn () => DB::table('requests')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($team, fn ($q) => $q->where('assigned_team', $team));

        $total = (int) $scope()->count();
        $completed = (int) $scope()->whereIn('status', ['completed', 'closed'])->count();
        $open = (int) $scope()->whereNotIn('status', ['completed', 'closed', 'rejected', 'cancelled'])->count();

        // Average resolution time in hours (closed requests only).
        $avgSeconds = $scope()
            ->whereNotNull('closed_at')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, created_at, closed_at)'));
        $avgResolutionHours = $avgSeconds ? round(((float) $avgSeconds) / 3600, 1) : 0;

        // SLA compliance: among closed requests, share not breached
        // (no deadline, or closed on/before the deadline).
        $closedTotal = (int) $scope()->whereNotNull('closed_at')->count();
        $compliant = (int) $scope()
            ->whereNotNull('closed_at')
            ->where(function ($q) {
                $q->whereNull('due_at')
                    ->orWhereColumn('closed_at', '<=', 'due_at');
            })
            ->count();
        $slaCompliance = $closedTotal > 0 ? round($compliant / $closedTotal * 100, 1) : 0;

        // CSAT from request_ratings (scoped to filtered requests).
        $ratingScope = fn () => DB::table('request_ratings')
            ->join('requests', 'requests.id', '=', 'request_ratings.request_id')
            ->when($from, fn ($q) => $q->whereDate('requests.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('requests.created_at', '<=', $to))
            ->when($team, fn ($q) => $q->where('requests.assigned_team', $team));

        $ratingsCount = (int) $ratingScope()->count();
        $csat = $ratingsCount > 0 ? round((float) $ratingScope()->avg('request_ratings.stars'), 2) : 0;
        $satisfied = (int) $ratingScope()->where('request_ratings.stars', '>=', 4)->count();
        $dissatisfied = (int) $ratingScope()->where('request_ratings.stars', '<=', 2)->count();

        $nps = $this->computeNps($from, $to, $team);

        $byStatus = $scope()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byPriority = $scope()
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $teams = DB::table('teams')->where('active', true)->orderBy('sort_order')
            ->get(['key', 'name_ar']);

        return Inertia::render('Reports/Index', [
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'open' => $open,
                'avg_resolution_hours' => $avgResolutionHours,
                'sla_compliance' => $slaCompliance,
                'csat' => $csat,
                'ratings_count' => $ratingsCount,
                'nps' => $nps,
                'satisfied' => $satisfied,
                'dissatisfied' => $dissatisfied,
            ],
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'teams' => $teams,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'team' => $team,
            ],
        ]);
    }

    /** Individual staff performance report over a date range. */
    public function employee(Request $request, Profile $profile)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $scope = fn () => DB::table('requests')
            ->where('assigned_to', $profile->id)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $assigned = (int) $scope()->count();
        $closed = (int) $scope()->whereIn('status', ['completed', 'closed'])->count();
        $open = (int) $scope()->whereNotIn('status', ['completed', 'closed', 'rejected', 'cancelled'])->count();
        $overdue = (int) $scope()
            ->whereNotIn('status', ['completed', 'closed', 'rejected', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
        $reopened = (int) $scope()->where('reopened_count', '>', 0)->count();

        $avgSeconds = $scope()
            ->whereNotNull('closed_at')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, created_at, closed_at)'));
        $avgResolutionHours = $avgSeconds ? round(((float) $avgSeconds) / 3600, 1) : 0;

        // First-response metrics.
        $avgRespSeconds = $scope()
            ->whereNotNull('first_response_at')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, created_at, first_response_at)'));
        $avgFirstResponseHours = $avgRespSeconds ? round(((float) $avgRespSeconds) / 3600, 1) : 0;

        $respTotal = (int) $scope()->whereNotNull('first_response_at')->count();
        $respCompliant = (int) $scope()
            ->whereNotNull('first_response_at')
            ->where(function ($q) {
                $q->whereNull('response_due_at')->orWhereColumn('first_response_at', '<=', 'response_due_at');
            })
            ->count();
        $responseSlaCompliance = $respTotal > 0 ? round($respCompliant / $respTotal * 100, 1) : 0;

        $closedTotal = (int) $scope()->whereNotNull('closed_at')->count();
        $compliant = (int) $scope()
            ->whereNotNull('closed_at')
            ->where(function ($q) {
                $q->whereNull('due_at')->orWhereColumn('closed_at', '<=', 'due_at');
            })
            ->count();
        $slaCompliance = $closedTotal > 0 ? round($compliant / $closedTotal * 100, 1) : 0;

        $ratingScope = fn () => DB::table('request_ratings')
            ->join('requests', 'requests.id', '=', 'request_ratings.request_id')
            ->where('requests.assigned_to', $profile->id)
            ->when($from, fn ($q) => $q->whereDate('requests.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('requests.created_at', '<=', $to));

        $ratingsCount = (int) $ratingScope()->count();
        $csat = $ratingsCount > 0 ? round((float) $ratingScope()->avg('request_ratings.stars'), 2) : 0;

        $recent = $scope()
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get(['id', 'request_number', 'title', 'status', 'priority', 'created_at', 'updated_at']);

        $roles = DB::table('user_roles')
            ->where('user_id', $profile->id)
            ->pluck('role')
            ->all();

        return Inertia::render('Reports/Employee', [
            'profile' => [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'city' => $profile->city,
                'region' => $profile->region,
                'team_id' => $profile->team_id,
                'roles' => $roles,
            ],
            'stats' => [
                'assigned' => $assigned,
                'closed' => $closed,
                'open' => $open,
                'overdue' => $overdue,
                'reopened' => $reopened,
                'avg_resolution_hours' => $avgResolutionHours,
                'avg_first_response_hours' => $avgFirstResponseHours,
                'sla_compliance' => $slaCompliance,
                'response_sla_compliance' => $responseSlaCompliance,
                'csat' => $csat,
                'ratings_count' => $ratingsCount,
            ],
            'recent' => $recent,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * NPS from request_extended_feedback when available, else derived from
     * CSAT ratings (5★ promoters, ≤3★ detractors).
     */
    private function computeNps($from, $to, $team): ?int
    {
        $fb = DB::table('request_extended_feedback')
            ->join('requests', 'requests.id', '=', 'request_extended_feedback.request_id')
            ->whereNotNull('request_extended_feedback.nps')
            ->when($from, fn ($q) => $q->whereDate('requests.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('requests.created_at', '<=', $to))
            ->when($team, fn ($q) => $q->where('requests.assigned_team', $team));

        $count = (int) $fb->count();
        if ($count > 0) {
            $promoters = (int) (clone $fb)->where('request_extended_feedback.nps', '>=', 9)->count();
            $detractors = (int) (clone $fb)->where('request_extended_feedback.nps', '<=', 6)->count();

            return (int) round(($promoters - $detractors) / $count * 100);
        }

        // Derive from CSAT ratings.
        $ratings = DB::table('request_ratings')
            ->join('requests', 'requests.id', '=', 'request_ratings.request_id')
            ->when($from, fn ($q) => $q->whereDate('requests.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('requests.created_at', '<=', $to))
            ->when($team, fn ($q) => $q->where('requests.assigned_team', $team));

        $rc = (int) $ratings->count();
        if ($rc === 0) {
            return null;
        }
        $promoters = (int) (clone $ratings)->where('request_ratings.stars', '>=', 5)->count();
        $detractors = (int) (clone $ratings)->where('request_ratings.stars', '<=', 3)->count();

        return (int) round(($promoters - $detractors) / $rc * 100);
    }
}
