<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasCapability($capability)) {
            abort(403, 'لا تملك صلاحية الوصول لهذه الصفحة.');
        }

        return $next($request);
    }
}
