<?php

namespace App\Http\Middleware;

use App\Enums\AdminRole;
use App\Enums\UserType;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || $user->type !== UserType::Admin) {
            return ApiResponse::error('Forbidden.', 403);
        }

        if ($roles === []) {
            return $next($request);
        }

        $allowed = collect($roles)
            ->map(fn (string $role) => AdminRole::tryFrom($role))
            ->filter()
            ->all();

        if (! in_array($user->admin_role, $allowed, true)) {
            return ApiResponse::error('Forbidden.', 403);
        }

        return $next($request);
    }
}
