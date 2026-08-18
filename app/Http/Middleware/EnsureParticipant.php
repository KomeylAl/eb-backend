<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Participant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Participant) {
            return ApiResponse::error($user ? 'Forbidden.' : 'Unauthenticated.', $user ? 403 : 401);
        }

        return $next($request);
    }
}
