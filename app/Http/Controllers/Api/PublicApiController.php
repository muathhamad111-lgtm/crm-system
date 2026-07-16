<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as CrmRequest;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    public function ping(Request $request)
    {
        $key = $request->attributes->get('api_key');

        return response()->json([
            'ok' => true,
            'prefix' => $key->prefix ?? null,
            'scopes' => $request->attributes->get('api_scopes', []),
            'at' => now()->toIso8601String(),
        ]);
    }

    /** Read-only recent requests — NO PII (mirrors /v1/requests). */
    public function requests(Request $request)
    {
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        $status = $request->query('status');

        $items = CrmRequest::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('created_at')
            ->limit($limit)
            ->get(['id', 'status', 'priority', 'category_id', 'created_at', 'updated_at', 'closed_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'status' => $r->status instanceof \BackedEnum ? $r->status->value : $r->status,
                'priority' => $r->priority instanceof \BackedEnum ? $r->priority->value : $r->priority,
                'category_id' => $r->category_id,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
                'closed_at' => $r->closed_at,
            ]);

        return response()->json(['count' => $items->count(), 'items' => $items]);
    }
}
