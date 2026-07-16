<?php

namespace App\Http\Controllers;

use App\Models\Request as CrmRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function home(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        return $this->index($request);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        // Fresh scoped query builder (customers see only their own requests).
        $scope = fn () => CrmRequest::query()
            ->when(! $isStaff, fn ($q) => $q->where('customer_id', $user->id));

        $active = $scope()->whereNotIn('status', ['closed', 'rejected', 'cancelled', 'completed'])->count();
        $pending = $scope()->whereIn('status', ['new', 'under_review'])->count();
        $completed = $scope()->whereIn('status', ['completed', 'closed'])->count();

        $csat = DB::table('request_ratings')
            ->when(! $isStaff, fn ($q) => $q->where('customer_id', $user->id))
            ->avg('stars');

        $recent = $scope()
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'request_number', 'title', 'status', 'priority', 'updated_at']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'active' => $active,
                'pending' => $pending,
                'completed' => $completed,
                'csat' => $csat ? round($csat, 1) : '—',
            ],
            'recent' => $recent,
        ]);
    }
}
