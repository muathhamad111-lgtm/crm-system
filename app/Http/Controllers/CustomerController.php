<?php

namespace App\Http\Controllers;

use App\Models\CustomerActivity;
use App\Models\CustomerAttachment;
use App\Models\CustomerContact;
use App\Models\CustomerSubscription;
use App\Models\Profile;
use App\Models\Request as CrmRequest;
use App\Models\RequestRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /** Staff directory of customer profiles with request + CSAT rollups. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $query = Profile::query()
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('region', 'like', $like);
                });
            })
            ->select('profiles.*')
            ->selectSub(
                CrmRequest::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('requests.customer_id', 'profiles.id'),
                'requests_count'
            )
            ->selectSub(
                CrmRequest::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('requests.customer_id', 'profiles.id')
                    ->whereNotIn('status', ['completed', 'closed', 'rejected', 'cancelled']),
                'open_requests_count'
            )
            ->selectSub(
                RequestRating::query()
                    ->selectRaw('round(avg(stars), 1)')
                    ->whereColumn('request_ratings.customer_id', 'profiles.id'),
                'avg_csat'
            )
            ->orderByDesc('requests_count')
            ->orderByDesc('last_contact_at');

        $customers = $query->paginate(20)->withQueryString();

        $totalCustomers = Profile::count();
        $avgCsat = RequestRating::query()->avg('stars');
        $totalRequests = CrmRequest::query()->count();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $search],
            'kpis' => [
                'total_customers' => $totalCustomers,
                'total_requests' => $totalRequests,
                'avg_csat' => $avgCsat ? round($avgCsat, 1) : null,
            ],
        ]);
    }

    /** Customer 360 — staff or the customer themselves may view. */
    public function show(Request $request, Profile $profile)
    {
        $user = $request->user();
        if (! $user->isStaff() && $user->id !== $profile->id) {
            abort(403, 'لا تملك صلاحية الاطلاع على هذا الملف.');
        }

        // Requests (with category + product + assignee names via joins).
        $requests = CrmRequest::query()
            ->where('requests.customer_id', $profile->id)
            ->leftJoin('categories', 'categories.id', '=', 'requests.category_id')
            ->leftJoin('products', 'products.id', '=', 'requests.product_id')
            ->leftJoin('profiles as assignee', 'assignee.id', '=', 'requests.assigned_to')
            ->orderByDesc('requests.created_at')
            ->limit(50)
            ->get([
                'requests.id',
                'requests.request_number',
                'requests.title',
                'requests.status',
                'requests.priority',
                'requests.created_at',
                'requests.due_at',
                'categories.name_ar as category_name',
                'products.name_ar as product_name',
                'assignee.full_name as assigned_name',
            ]);

        $contacts = CustomerContact::query()
            ->where('customer_id', $profile->id)
            ->orderByDesc('is_primary')
            ->orderBy('full_name')
            ->get();

        $subscriptions = CustomerSubscription::query()
            ->where('customer_id', $profile->id)
            ->orderByDesc('start_date')
            ->get();

        $activities = CustomerActivity::query()
            ->where('customer_id', $profile->id)
            ->orderByDesc('occurred_at')
            ->limit(80)
            ->get();

        $ratings = RequestRating::query()
            ->where('request_ratings.customer_id', $profile->id)
            ->leftJoin('requests', 'requests.id', '=', 'request_ratings.request_id')
            ->orderByDesc('request_ratings.created_at')
            ->get([
                'request_ratings.id',
                'request_ratings.stars',
                'request_ratings.notes',
                'request_ratings.created_at',
                'request_ratings.request_id',
                'requests.request_number',
                'requests.title as request_title',
            ]);

        $attachments = CustomerAttachment::query()
            ->where('customer_id', $profile->id)
            ->orderByDesc('created_at')
            ->get();

        // Roll-up stats for the KPI ribbon.
        $now = now();
        $total = $requests->count();
        $allRequestCount = CrmRequest::query()->where('customer_id', $profile->id)->count();
        $openCount = CrmRequest::query()
            ->where('customer_id', $profile->id)
            ->whereNotIn('status', ['completed', 'closed', 'rejected', 'cancelled'])
            ->count();
        $overdueCount = CrmRequest::query()
            ->where('customer_id', $profile->id)
            ->whereNotIn('status', ['completed', 'closed', 'rejected', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', $now)
            ->count();
        $closedCount = CrmRequest::query()
            ->where('customer_id', $profile->id)
            ->whereIn('status', ['completed', 'closed'])
            ->count();

        $csatAvg = $ratings->avg('stars');
        $activeSubs = $subscriptions->where('status', 'active')->count();

        return Inertia::render('Customers/Show', [
            'profile' => $profile,
            'requests' => $requests,
            'contacts' => $contacts,
            'subscriptions' => $subscriptions,
            'activities' => $activities,
            'ratings' => $ratings,
            'attachments' => $attachments,
            'canManage' => $user->isStaff(),
            'stats' => [
                'requests_total' => $allRequestCount,
                'requests_recent' => $total,
                'requests_open' => $openCount,
                'requests_overdue' => $overdueCount,
                'requests_closed' => $closedCount,
                'contacts_count' => $contacts->count(),
                'subscriptions_count' => $subscriptions->count(),
                'active_subscriptions' => $activeSubs,
                'csat' => $csatAvg ? round($csatAvg, 1) : null,
                'csat_count' => $ratings->count(),
            ],
        ]);
    }
}
