<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RatingController extends Controller
{
    /** CSAT list — customers see their own ratings, staff see all. */
    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        $filters = [
            'stars' => $request->query('stars', 'all'),
            'field' => $request->query('field', 'all'),
            'q' => $request->query('q', ''),
        ];

        $q = DB::table('request_ratings as rr')
            ->leftJoin('requests as r', 'r.id', '=', 'rr.request_id')
            ->leftJoin('categories as c', 'c.id', '=', 'r.category_id')
            ->leftJoin('profiles as p', 'p.id', '=', 'rr.customer_id')
            ->orderByDesc('rr.created_at');

        if (! $isStaff) {
            $q->where('rr.customer_id', $user->id);
        }
        if ($filters['stars'] !== 'all') {
            $q->where('rr.stars', (int) $filters['stars']);
        }
        if ($isStaff && $filters['field'] !== 'all') {
            $q->where('p.business_field', $filters['field']);
        }
        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $q->where(fn ($w) => $w->where('p.full_name', 'like', $term)
                ->orWhere('p.email', 'like', $term)
                ->orWhere('r.request_number', 'like', $term)
                ->orWhere('r.title', 'like', $term)
                ->orWhere('c.name_ar', 'like', $term));
        }

        $rows = $q->get([
            'rr.id', 'rr.stars', 'rr.notes', 'rr.dissatisfaction_reasons', 'rr.created_at', 'rr.request_id',
            'r.request_number', 'r.title', 'r.status',
            'c.name_ar as category_name',
            'p.full_name as customer_name', 'p.email as customer_email', 'p.business_field',
        ])->map(function ($r) {
            $reasons = $r->dissatisfaction_reasons;
            if (is_string($reasons)) {
                $decoded = json_decode($reasons, true);
                $reasons = is_array($decoded) ? $decoded : [];
            }
            $r->dissatisfaction_reasons = $reasons ?: [];

            return $r;
        });

        // KPIs across the (unpaginated) result set.
        $total = $rows->count();
        $avg = $total ? round($rows->avg('stars'), 2) : 0;
        $promoters = $rows->where('stars', '>=', 4)->count();
        $detractors = $rows->where('stars', '<=', 2)->count();

        // Business-field options (staff filter).
        $businessFields = $isStaff
            ? DB::table('request_ratings as rr')
                ->leftJoin('profiles as p', 'p.id', '=', 'rr.customer_id')
                ->whereNotNull('p.business_field')
                ->distinct()
                ->orderBy('p.business_field')
                ->pluck('p.business_field')
                ->all()
            : [];

        return Inertia::render('Ratings/Index', [
            'ratings' => $rows->values(),
            'stats' => [
                'total' => $total,
                'avg' => $avg,
                'promoters' => $promoters,
                'detractors' => $detractors,
            ],
            'filters' => $filters,
            'businessFields' => $businessFields,
            'isStaff' => $isStaff,
        ]);
    }
}
