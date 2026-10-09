<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check() || !in_array(auth()->user()->rola, $roles)) {
            abort(403, 'Brak dostępu.');
        }
        return $next($request);
    }
}
