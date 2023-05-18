<?php

namespace App\Http\Middleware;

use App\Helpers\AuthorizationChecker;
use App\Models\AccessProfile;
use App\Models\UserAccessProfile;
use Closure;
use Illuminate\Http\Request;

class IsUserManagerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $auth_check = AuthorizationChecker::check_auth("User Management");
        if (!$auth_check) {
            return response()->json([
                'message' => 'unauthorized'
            ], 403);
        }
        return $next($request);
    }
}
