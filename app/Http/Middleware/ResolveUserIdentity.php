<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveUserIdentity
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized. Missing X-User-Id header.'], 401);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized. User not found.'], 401);
        }

        Auth::setUser($user);

        return $next($request);
    }
}