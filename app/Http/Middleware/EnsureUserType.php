<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    /**
     * @param  string  ...$types
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        $allowed = collect($types)
            ->map(fn (string $type) => UserType::tryFrom($type))
            ->filter()
            ->all();

        if ($allowed === [] || ! in_array($user->type, $allowed, true)) {
            return ApiResponse::error('Forbidden.', 403);
        }

        return $next($request);
    }
}
