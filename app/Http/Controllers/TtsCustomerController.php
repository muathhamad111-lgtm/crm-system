<?php

namespace App\Http\Controllers;

use App\Models\TtsContact;
use App\Models\TtsCustomer;
use App\Models\TtsSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TtsCustomerController extends Controller
{
    /** Synced store customers with contact + subscription counts. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $entity = (string) $request->query('entity', 'all');
        $sort = (string) $request->query('sort', 'synced');
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Whitelisted sortable columns (real columns + derived count aliases).
        $sortMap = [
            'name' => 'full_name',
            'entity' => 'entity_type',
            'contact' => 'email',
            'active' => 'active_subscriptions_count',
            'contacts' => 'contacts_count',
            'synced' => 'last_synced_at',
        ];
        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'synced';
        }

        $query = TtsCustomer::query()
            ->whereNull('deleted_at')
            ->when($entity !== 'all' && $entity !== '', fn ($q) => $q->where('entity_type', $entity))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('external_id', 'like', $like)
                        ->orWhere('license_no', 'like', $like)
                        ->orWhere('tax_no', 'like', $like)
                        ->orWhere('business_field', 'like', $like);
                });
            })
            ->select('tts_customers.*')
            ->selectSub(
                TtsContact::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('tts_contacts.customer_id', 'tts_customers.id')
                    ->whereNull('deleted_at'),
                'contacts_count'
            )
            ->selectSub(
                TtsSubscription::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('tts_subscriptions.customer_id', 'tts_customers.id')
                    ->whereNull('deleted_at')
                    ->where('status', 'active'),
                'active_subscriptions_count'
            )
            ->orderBy($sortMap[$sort], $dir);

        $customers = $query->paginate(25)->withQueryString();

        $base = TtsCustomer::query()->whereNull('deleted_at');

        $kpis = [
            'total_customers' => (clone $base)->count(),
            'organizations' => (clone $base)->whereIn('entity_type', ['non_profit', 'private_org'])->count(),
            'active_subscriptions' => TtsSubscription::query()
                ->whereNull('deleted_at')->where('status', 'active')->count(),
            'contacts' => TtsContact::query()->whereNull('deleted_at')->count(),
        ];

        return Inertia::render('TtsCustomers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $search, 'entity' => $entity, 'sort' => $sort, 'dir' => $dir],
            'kpis' => $kpis,
        ]);
    }

    /** Store customer detail + contacts + subscriptions. */
    public function show(TtsCustomer $ttsCustomer)
    {
        $contacts = TtsContact::query()
            ->where('customer_id', $ttsCustomer->id)
            ->whereNull('deleted_at')
            ->orderBy('role_type')
            ->get();

        $subscriptions = TtsSubscription::query()
            ->where('customer_id', $ttsCustomer->id)
            ->whereNull('deleted_at')
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('TtsCustomers/Show', [
            'customer' => $ttsCustomer,
            'contacts' => $contacts,
            'subscriptions' => $subscriptions,
            'stats' => [
                'contacts_count' => $contacts->count(),
                'subscriptions_count' => $subscriptions->count(),
                'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
            ],
        ]);
    }
}
