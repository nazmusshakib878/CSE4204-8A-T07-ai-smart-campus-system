<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if ($request->user()?->approval_status !== 'approved') {
            $request->user()?->currentAccessToken()?->delete();

            return response()->json([
                'status' => false,
                'message' => 'Your account is no longer approved. Please contact an administrator.',
            ], 403);
        }

        return $next($request);
    }
}