<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next, string $role = 'staff'): Response
    {
        $user = $request->user();
        abort_unless($user && ($role === 'admin' ? $user->isAdmin() : $user->isStaff()), 403);

        return $next($request);
    }
}
