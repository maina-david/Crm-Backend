<?php

namespace App\Http\Middleware;

use App\Helpers\AuthorizationChecker;
use Closure;
use Illuminate\Http\Request;

class IsChatFlowManagerMiddleware
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
        $auth_check = AuthorizationChecker::check_auth("Chat Flow Manager");
        if (!$auth_check) {
            return response()->json([
                'message' => 'unauthorized',
                'access_right'=>"Chat Flow Manager"
            ], 403);
        }
        return $next($request);
    }
}