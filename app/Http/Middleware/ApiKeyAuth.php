<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Authenticates public API requests via a hashed API key (port of withApiKey). */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $raw = $request->bearerToken() ?: $request->header('X-Api-Key');
        if (! $raw) {
            return response()->json(['error' => 'missing_api_key'], 401);
        }

        $hash = hash('sha256', $raw);
        $key = DB::table('api_keys')->where('key_hash', $hash)->first();

        if (! $key || $key->revoked_at) {
            return response()->json(['error' => 'invalid_api_key'], 401);
        }

        $scopes = json_decode($key->scopes ?? '[]', true) ?: [];
        if ($scope && ! in_array($scope, $scopes, true)) {
            return response()->json(['error' => 'insufficient_scope', 'required' => $scope], 403);
        }

        DB::table('api_keys')->where('id', $key->id)->update(['last_used_at' => Carbon::now()]);

        $request->attributes->set('api_key', $key);
        $request->attributes->set('api_scopes', $scopes);

        return $next($request);
    }
}
