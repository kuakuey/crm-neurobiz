<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $allowed = array_map(fn ($role) => Role::from($role), $roles);
        if (! in_array($user->role, $allowed, true) && $user->role !== Role::Admin) {
            abort(403, 'No tienes permiso para esta sección.');
        }

        return $next($request);
    }
}
