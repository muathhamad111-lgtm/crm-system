<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Multi-tab reports & analytics dashboard.
     *
     * Reads the shared filter bar (from/to/range + region/city/category/
     * sub_category/customer/product/business_field), builds a scoped base
     * query over `requests`, and computes the aggregates for every tab in
     * one pass (the page switches tabs client-side). All aggregation is done
     * with grouped SQL queries — no per-row loops.
     */
    public function index(Request $request)
    {
        [$from, $to, $range] = $this->resolveRange($request);

        $region = $request->query('region');
        $city = $request->query('city');
        $businessField = $request->query('business_field');
        $category = $request->query('category');
        $subCategory = $request->query('sub_category');
        $customer = $request->query('customer');
        $product = $request->query('product');

        // Apply the shared filters to any query already aliased with r/cust/cat.
        $applyFilters = function ($q) use (
            $from, $to, $region, $city, $businessField, $category, $subCategory, $customer, $product
        ) {
            return $q
                ->when($from, fn ($x) => $x->whereDate('r.created_at', '>=', $from))
                ->when($to, fn ($x) => $x->whereDate('r.created_at', '<=', $to))
                ->when($region, fn ($x) => $x->where('cust.region', $region))
                ->when($city, fn ($x) => $x->where('cust.city', $city))
                ->when($businessField, fn ($x) => $x->where('cust.business_field', $businessField))
                ->when($category, fn ($x) => $x->where('r.category_id', $category))
                ->when($subCategory, fn ($x) => $x->where('r.sub_category_id', $subCategory))
                ->when($customer, fn ($x) => $x->where('r.customer_id', $customer))
                ->when($product, fn ($x) => $x->where('r.product_id', $product));
        };

        // Tickets = every request whose category is NOT a suggestion bucket.
        $ticketScope = function () use ($applyFilters) {
            $q = DB::table('requests as r')
                ->leftJoin('profiles as cust', 'cust.id', '=', 'r.customer_id')
                ->leftJoin('categories as cat', 'cat.id', '=', 'r.category_id')
                ->where(fn ($w) => $w->where('cat.is_suggestion', false)->orWhereNull('cat.is_suggestion'));

            return $applyFilters($q);
        };

        // Suggestions = requests filed under a suggestion category.
        $suggestionScope = function () use ($applyFilters) {
            $q = DB::table('requests as r')
                ->leftJoin('profiles as cust', 'cust.id', '=', 'r.customer_id')
                ->join('categories as cat', 'cat.id', '=', 'r.category_id')
                ->where('cat.is_suggestion', true);

            return $applyFilters($q);
        };

        // Ratings joined back to their (ticket-scoped) request for CSAT metrics.
        $ratingScope = function () use ($applyFilters) {
            $q = DB::table('request_ratings as rr')
                ->join('requests as r', 'r.id', '=', 'rr.request_id')
                ->leftJoin('profiles as cust', 'cust.id', '=', 'r.customer_id')
                ->leftJoin('categories as cat', 'cat.id', '=', 'r.category_id')
                ->where(fn ($w) => $w->where('cat.is_suggestion', false)->orWhereNull('cat.is_suggestion'));

            return $applyFilters($q);
        };

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'range' => $range,
                'region' => $region,
                'city' => $city,
                'business_field' => $businessField,
                'category' => $category,
                'sub_category' => $subCategory,
                'customer' => $customer,
                'product' => $product,
            ],
            'options' => $this->filterOptions(),
            'customer' => $this->customerReport($ticketScope, $ratingScope),
            'requests' => $this->requestsReport($ticketScope),
            'suggestions' => $this->suggestionsReport($suggestionScope),
            'products' => $this->productsReport($ticketScope, $ratingScope),
            'team' => $this->teamReport($ticketScope, $ratingScope),
            'sla' => $this->slaReport($ticketScope),
        ]);
    }

    /* ============================ TAB BUILDERS ============================ */

    private function customerReport(\Closure $ticketScope, \Closure $ratingScope): array
    {
        $total = (int) $ticketScope()->count();
        $completed = (int) $ticketScope()->whereIn('r.status', ['completed', 'closed'])->count();

        $avgSec = $ticketScope()->whereNotNull('r.closed_at')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, r.created_at, r.closed_at)'));
        $avgResolutionHours = $avgSec ? round(((float) $avgSec) / 3600, 1) : 0;

        $closedTotal = (int) $ticketScope()->whereNotNull('r.closed_at')->count();
        $compliant = (int) $ticketScope()->whereNotNull('r.closed_at')
            ->where(fn ($q) => $q->whereNull('r.due_at')->orWhereColumn('r.closed_at', '<=', 'r.due_at'))
            ->count();
        $slaCompliance = $closedTotal > 0 ? round($compliant / $closedTotal * 100, 1) : 0;

        $ratingsCount = (int) $ratingScope()->count();
        $csat = $ratingsCount > 0 ? round((float) $ratingScope()->avg('rr.stars'), 2) : 0;
        $satisfied = (int) $ratingScope()->where('rr.stars', '>=', 4)->count();
        $dissatisfied = (int) $ratingScope()->where('rr.stars', '<=', 2)->count();
        $nps = $ratingsCount > 0 ? (int) round(($satisfied - $dissatisfied) / $ratingsCount * 100) : null;

        // Status donut.
        $byStatus = $ticketScope()->select('r.status as k', DB::raw('count(*) as c'))
            ->groupBy('r.status')->pluck('c', 'k');

        // Star distribution 1..5.
        $starsRaw = $ratingScope()->select('rr.stars as s', DB::raw('count(*) as c'))
            ->groupBy('rr.stars')->pluck('c', 's');
        $starsDist = collect(range(1, 5))->map(fn ($s) => [
            'label' => $s.'★',
            'value' => (int) ($starsRaw[$s] ?? 0),
        ])->all();

        // CSAT trend over time (avg stars + count per day).
        $csatTrend = $ratingScope()
            ->select(DB::raw('DATE(rr.created_at) as d'), DB::raw('avg(rr.stars) as avg'), DB::raw('count(*) as c'))
            ->groupBy(DB::raw('DATE(rr.created_at)'))->orderBy('d')
            ->get()
            ->map(fn ($r) => [
                'date' => (string) $r->d,
                'csat' => round((float) $r->avg, 2),
                'count' => (int) $r->c,
            ])->all();

        // Avg satisfaction per product.
        $avgSatPerProduct = $ratingScope()
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->select('p.name_ar as name', DB::raw('avg(rr.stars) as avg'), DB::raw('count(*) as c'))
            ->groupBy('p.name_ar')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name ?: 'بدون منتج',
                'avg' => round((float) $r->avg, 2),
                'count' => (int) $r->c,
            ])
            ->sortByDesc('avg')->values()->take(10)->all();

        // Per-customer satisfaction → happiest / unhappiest.
        $byCustomerSat = $ratingScope()
            ->select('r.customer_id as id', 'cust.full_name as name',
                DB::raw('avg(rr.stars) as avg'), DB::raw('count(*) as n'),
                DB::raw('sum(case when rr.stars <= 2 then 1 else 0 end) as neg'))
            ->groupBy('r.customer_id', 'cust.full_name')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name ?: '—',
                'avg' => round((float) $r->avg, 2),
                'n' => (int) $r->n,
                'neg' => (int) $r->neg,
            ]);
        $topHappy = $byCustomerSat->sortByDesc('avg')->values()->take(6)->all();
        $topUnhappy = $byCustomerSat->sortBy('avg')->values()->take(6)->all();

        // Dissatisfaction reasons (JSON array column) aggregated in PHP.
        $reasonCounts = [];
        foreach ($ratingScope()->whereNotNull('rr.dissatisfaction_reasons')->pluck('rr.dissatisfaction_reasons') as $raw) {
            $arr = is_array($raw) ? $raw : json_decode($raw, true);
            if (! is_array($arr)) {
                continue;
            }
            foreach ($arr as $reason) {
                if ($reason === null || $reason === '') {
                    continue;
                }
                $reasonCounts[$reason] = ($reasonCounts[$reason] ?? 0) + 1;
            }
        }
        arsort($reasonCounts);
        $dissatisfactionReasons = collect($reasonCounts)->take(8)
            ->map(fn ($n, $name) => ['name' => $name, 'value' => $n])->values()->all();

        // Requests by sub-category.
        $bySubCategory = $ticketScope()
            ->leftJoin('request_sub_categories as sc', 'sc.id', '=', 'r.sub_category_id')
            ->select('sc.name_ar as name', DB::raw('count(*) as c'))
            ->groupBy('sc.name_ar')->orderByDesc('c')->limit(12)
            ->get()
            ->map(fn ($r) => ['name' => $r->name ?: '— غير محدد —', 'value' => (int) $r->c])->all();

        return [
            'kpis' => [
                'total' => $total,
                'completed' => $completed,
                'avg_resolution_hours' => $avgResolutionHours,
                'sla_compliance' => $slaCompliance,
                'csat' => $csat,
                'ratings_count' => $ratingsCount,
                'satisfied' => $satisfied,
                'dissatisfied' => $dissatisfied,
                'nps' => $nps,
            ],
            'byStatus' => $byStatus,
            'starsDist' => $starsDist,
            'csatTrend' => $csatTrend,
            'avgSatPerProduct' => $avgSatPerProduct,
            'topHappy' => $topHappy,
            'topUnhappy' => $topUnhappy,
            'dissatisfactionReasons' => $dissatisfactionReasons,
            'bySubCategory' => $bySubCategory,
        ];
    }

    private function requestsReport(\Closure $ticketScope): array
    {
        $total = (int) $ticketScope()->count();
        $completed = (int) $ticketScope()->whereIn('r.status', ['completed', 'closed'])->count();
        $openStatuses = ['completed', 'closed', 'rejected', 'cancelled'];
        $open = (int) $ticketScope()->whereNotIn('r.status', $openStatuses)->count();
        $overdue = (int) $ticketScope()
            ->whereNotIn('r.status', $openStatuses)
            ->whereNotNull('r.due_at')->where('r.due_at', '<', now())
            ->count();

        $avgSec = $ticketScope()->whereNotNull('r.closed_at')
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, r.created_at, r.closed_at)'));

        $byStatus = $ticketScope()->select('r.status as k', DB::raw('count(*) as c'))
            ->groupBy('r.status')->pluck('c', 'k');
        $byPriority = $ticketScope()->select('r.priority as k', DB::raw('count(*) as c'))
            ->groupBy('r.priority')->pluck('c', 'k');
        $byCategory = $ticketScope()->select('cat.name_ar as name', DB::raw('count(*) as c'))
            ->groupBy('cat.name_ar')->orderByDesc('c')->limit(12)
            ->get()->map(fn ($r) => ['name' => $r->name ?: '—', 'value' => (int) $r->c])->all();

        return [
            'total' => $total,
            'open' => $open,
            'completed' => $completed,
            'overdue' => $overdue,
            'avg_resolution_hours' => $avgSec ? round(((float) $avgSec) / 3600, 1) : 0,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'byCategory' => $byCategory,
        ];
    }

    private function suggestionsReport(\Closure $suggestionScope): array
    {
        $total = (int) $suggestionScope()->count();
        $published = (int) $suggestionScope()->where('r.published_to_customers', true)->count();
        $submitters = (int) $suggestionScope()->whereNotNull('r.customer_id')->distinct()->count('r.customer_id');

        $byStage = $suggestionScope()->select('r.idea_stage as k', DB::raw('count(*) as c'))
            ->groupBy('r.idea_stage')->pluck('c', 'k');

        $ids = fn () => $suggestionScope()->select('r.id');

        $votes = (int) DB::table('suggestion_votes as sv')
            ->joinSub($ids(), 'sug', 'sug.id', '=', 'sv.request_id')->count();
        $comments = (int) DB::table('suggestion_comments as sc2')
            ->joinSub($ids(), 'sug', 'sug.id', '=', 'sc2.request_id')
            ->whereNull('sc2.deleted_at')->count();
        $ratingCount = (int) DB::table('suggestion_ratings as sr')
            ->joinSub($ids(), 'sug', 'sug.id', '=', 'sr.request_id')->count();
        $ratingAvg = $ratingCount > 0 ? round((float) DB::table('suggestion_ratings as sr')
            ->joinSub($ids(), 'sug', 'sug.id', '=', 'sr.request_id')->avg('sr.stars'), 2) : 0;

        $topSubmitters = $suggestionScope()->whereNotNull('r.customer_id')
            ->select('r.customer_id as id', 'cust.full_name as name', DB::raw('count(*) as n'))
            ->groupBy('r.customer_id', 'cust.full_name')->orderByDesc('n')->limit(10)
            ->get()->map(fn ($r) => ['id' => $r->id, 'name' => $r->name ?: '—', 'n' => (int) $r->n])->all();

        return [
            'total' => $total,
            'published' => $published,
            'submitters' => $submitters,
            'votes' => $votes,
            'comments' => $comments,
            'rating_avg' => $ratingAvg,
            'rating_count' => $ratingCount,
            'byStage' => $byStage,
            'topSubmitters' => $topSubmitters,
        ];
    }

    private function productsReport(\Closure $ticketScope, \Closure $ratingScope): array
    {
        $requestsPerProduct = $ticketScope()
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->select('p.name_ar as name', DB::raw('count(*) as c'))
            ->groupBy('p.name_ar')->orderByDesc('c')->limit(12)
            ->get()->map(fn ($r) => ['name' => $r->name ?: 'بدون منتج', 'value' => (int) $r->c])->all();

        $csatPerProduct = $ratingScope()
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->select('p.name_ar as name', DB::raw('avg(rr.stars) as avg'), DB::raw('count(*) as c'))
            ->groupBy('p.name_ar')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name ?: 'بدون منتج',
                'avg' => round((float) $r->avg, 2),
                'count' => (int) $r->c,
            ])->sortByDesc('avg')->values()->take(12)->all();

        return [
            'requestsPerProduct' => $requestsPerProduct,
            'csatPerProduct' => $csatPerProduct,
        ];
    }

    private function teamReport(\Closure $ticketScope, \Closure $ratingScope): array
    {
        $rows = $ticketScope()
            ->whereNotNull('r.assigned_to')
            ->leftJoin('profiles as asg', 'asg.id', '=', 'r.assigned_to')
            ->select('r.assigned_to as id', 'asg.full_name as name',
                DB::raw('count(*) as assigned'),
                DB::raw("sum(case when r.status in ('completed','closed') then 1 else 0 end) as closed"),
                DB::raw('sum(case when r.closed_at is not null and (r.due_at is null or r.closed_at <= r.due_at) then 1 else 0 end) as sla_ok'),
                DB::raw('sum(case when r.closed_at is not null then 1 else 0 end) as closed_total'),
                DB::raw('avg(case when r.closed_at is not null then TIMESTAMPDIFF(SECOND, r.created_at, r.closed_at) end) as avg_sec'))
            ->groupBy('r.assigned_to', 'asg.full_name')
            ->orderByDesc('assigned')
            ->get();

        $csatByEmp = $ratingScope()->whereNotNull('r.assigned_to')
            ->select('r.assigned_to as id', DB::raw('avg(rr.stars) as avg'), DB::raw('count(*) as n'))
            ->groupBy('r.assigned_to')->get()->keyBy('id');

        return $rows->map(function ($r) use ($csatByEmp) {
            $assigned = (int) $r->assigned;
            $slaOk = (int) $r->sla_ok;
            $closedTotal = (int) $r->closed_total;
            $csat = $csatByEmp->get($r->id);

            return [
                'id' => $r->id,
                'name' => $r->name ?: '—',
                'assigned' => $assigned,
                'closed' => (int) $r->closed,
                'sla_ok' => $slaOk,
                'sla_pct' => $closedTotal > 0 ? (int) round($slaOk / $closedTotal * 100) : 0,
                'avg_hours' => $r->avg_sec ? round(((float) $r->avg_sec) / 3600, 1) : 0,
                'csat' => $csat ? round((float) $csat->avg, 2) : null,
                'csat_n' => $csat ? (int) $csat->n : 0,
            ];
        })->all();
    }

    private function slaReport(\Closure $ticketScope): array
    {
        $closedTotal = (int) $ticketScope()->whereNotNull('r.closed_at')->count();
        $compliant = (int) $ticketScope()->whereNotNull('r.closed_at')
            ->where(fn ($q) => $q->whereNull('r.due_at')->orWhereColumn('r.closed_at', '<=', 'r.due_at'))
            ->count();
        $compliance = $closedTotal > 0 ? round($compliant / $closedTotal * 100, 1) : 0;

        $mapCompliance = fn ($r) => [
            'name' => $r->name ?: '—',
            'total' => (int) $r->total,
            'ok' => (int) $r->ok,
            'pct' => (int) $r->total > 0 ? (int) round(((int) $r->ok) / ((int) $r->total) * 100) : 0,
        ];

        $byPriority = $ticketScope()->whereNotNull('r.closed_at')
            ->select('r.priority as name', DB::raw('count(*) as total'),
                DB::raw('sum(case when r.due_at is null or r.closed_at <= r.due_at then 1 else 0 end) as ok'))
            ->groupBy('r.priority')->get()->map($mapCompliance)->all();

        $byCategory = $ticketScope()->whereNotNull('r.closed_at')
            ->select('cat.name_ar as name', DB::raw('count(*) as total'),
                DB::raw('sum(case when r.due_at is null or r.closed_at <= r.due_at then 1 else 0 end) as ok'))
            ->groupBy('cat.name_ar')->orderByDesc('total')->limit(12)->get()->map($mapCompliance)->all();

        $topStages = DB::table('request_stage_sla_log as sl')
            ->joinSub($ticketScope()->select('r.id'), 'tr', 'tr.id', '=', 'sl.request_id')
            ->where('sl.breached', true)
            ->select('sl.stage_name as name', DB::raw('count(*) as c'))
            ->groupBy('sl.stage_name')->orderByDesc('c')->limit(10)
            ->get()->map(fn ($r) => ['name' => $r->name ?: '—', 'value' => (int) $r->c])->all();

        return [
            'compliance' => $compliance,
            'closed_total' => $closedTotal,
            'compliant' => $compliant,
            'byPriority' => $byPriority,
            'byCategory' => $byCategory,
            'topStages' => $topStages,
        ];
    }

    /* ============================ EXPORT ============================ */

    /** Stream the filtered requests as a CSV (BOM + UTF-8 for Excel/Arabic). */
    public function export(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        $region = $request->query('region');
        $city = $request->query('city');
        $businessField = $request->query('business_field');
        $category = $request->query('category');
        $subCategory = $request->query('sub_category');
        $customer = $request->query('customer');
        $product = $request->query('product');

        $rows = DB::table('requests as r')
            ->leftJoin('profiles as cust', 'cust.id', '=', 'r.customer_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'r.category_id')
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->leftJoin('request_ratings as rr', 'rr.request_id', '=', 'r.id')
            ->when($from, fn ($x) => $x->whereDate('r.created_at', '>=', $from))
            ->when($to, fn ($x) => $x->whereDate('r.created_at', '<=', $to))
            ->when($region, fn ($x) => $x->where('cust.region', $region))
            ->when($city, fn ($x) => $x->where('cust.city', $city))
            ->when($businessField, fn ($x) => $x->where('cust.business_field', $businessField))
            ->when($category, fn ($x) => $x->where('r.category_id', $category))
            ->when($subCategory, fn ($x) => $x->where('r.sub_category_id', $subCategory))
            ->when($customer, fn ($x) => $x->where('r.customer_id', $customer))
            ->when($product, fn ($x) => $x->where('r.product_id', $product))
            ->orderByDesc('r.created_at')
            ->limit(10000)
            ->get([
                'r.request_number', 'r.title', 'r.status', 'r.priority',
                'cat.name_ar as category', 'p.name_ar as product', 'cust.full_name as customer',
                'r.created_at', 'r.closed_at', 'rr.stars as rating',
                DB::raw('CASE WHEN r.closed_at IS NOT NULL THEN ROUND(TIMESTAMPDIFF(SECOND, r.created_at, r.closed_at) / 3600, 1) END as resolution_hours'),
            ]);

        $headers = [
            'request_number' => 'رقم الطلب',
            'title' => 'العنوان',
            'status' => 'الحالة',
            'priority' => 'الأولوية',
            'category' => 'التصنيف',
            'product' => 'المنتج/الخدمة',
            'customer' => 'العميل',
            'created_at' => 'أنشئ في',
            'closed_at' => 'أُغلق في',
            'resolution_hours' => 'مدة الحل (ساعة)',
            'rating' => 'التقييم',
        ];

        $filename = 'reports-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, array_values($headers));
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->request_number,
                    $row->title,
                    $row->status,
                    $row->priority,
                    $row->category,
                    $row->product,
                    $row->customer,
                    $row->created_at,
                    $row->closed_at,
                    $row->resolution_hours,
                    $row->rating,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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

    /* ============================ HELPERS ============================ */

    /**
     * Resolve the effective [from, to, range] from the request. A preset
     * range ("7"/"30"/"90"/"all") derives the dates; "custom" honours the
     * explicit from/to inputs.
     *
     * @return array{0: ?string, 1: ?string, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $range = (string) $request->query('range', '30');
        $from = $request->query('from');
        $to = $request->query('to');

        if ($range !== 'custom') {
            $to = Carbon::today()->toDateString();
            $from = match ($range) {
                'all' => null,
                '7' => Carbon::today()->subDays(7)->toDateString(),
                '90' => Carbon::today()->subDays(90)->toDateString(),
                default => Carbon::today()->subDays(30)->toDateString(),
            };
            if ($range === 'all') {
                $to = null;
            }
        }

        return [$from, $to, $range];
    }

    /** Option lists for the filter bar. */
    private function filterOptions(): array
    {
        $categories = DB::table('categories')
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'is_suggestion']);

        $subCategories = DB::table('request_sub_categories')
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'category_id']);

        $products = DB::table('products')
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar']);

        $customers = DB::table('profiles')
            ->join('user_roles', 'user_roles.user_id', '=', 'profiles.id')
            ->where('user_roles.role', 'customer')
            ->orderBy('profiles.full_name')
            ->limit(500)
            ->distinct()
            ->get(['profiles.id', 'profiles.full_name', 'profiles.email']);

        $regions = DB::table('profiles')->whereNotNull('region')->where('region', '!=', '')
            ->distinct()->orderBy('region')->pluck('region');
        $cities = DB::table('profiles')->whereNotNull('city')->where('city', '!=', '')
            ->distinct()->orderBy('city')->pluck('city');
        $businessFields = DB::table('profiles')->whereNotNull('business_field')->where('business_field', '!=', '')
            ->distinct()->orderBy('business_field')->pluck('business_field');

        return [
            'categories' => $categories,
            'subCategories' => $subCategories,
            'products' => $products,
            'customers' => $customers,
            'regions' => $regions,
            'cities' => $cities,
            'businessFields' => $businessFields,
        ];
    }
}
