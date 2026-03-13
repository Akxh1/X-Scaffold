<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle role-based access control.
     *
     * Usage in routes:
     *   ->middleware('role:student')
     *   ->middleware('role:teacher')       // admin also passes
     *   ->middleware('role:teacher,admin') // explicit multi-role
     *
     * Admin role always has super-access to ALL routes.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin super-access: always allow
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check if user's role is in the allowed list
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        // Unauthorized — show themed 403 page
        return response()->view('errors.403', [
            'userRole' => $user->role,
            'requiredRoles' => $roles,
        ], 403);
    }
}
