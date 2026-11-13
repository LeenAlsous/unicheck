<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Only lets users with one of the given roles through, e.g. ->middleware('role:instructor,admin')
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! in_array($request->user()?->role, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
