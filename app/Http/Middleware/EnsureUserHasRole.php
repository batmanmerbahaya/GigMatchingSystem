<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Abort with 403 unless the authenticated user has the given role.
     * Used as `role:employer` / `role:worker` on route groups so users
     * can never reach (or even probe) the other role's pages.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || $user->role->value !== $role) {
            abort(403);
        }

        return $next($request);
    }
}
