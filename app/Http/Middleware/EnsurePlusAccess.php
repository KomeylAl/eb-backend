<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Participant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlusAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $allowed = $user instanceof Participant
            || ($user instanceof User && $user->isClient());

        if (! $allowed) {
            return ApiResponse::error($user ? 'Forbidden.' : 'Unauthenticated.', $user ? 403 : 401);
        }

        return $next($request);
    }
}
