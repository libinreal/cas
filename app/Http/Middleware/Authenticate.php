<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        //If request has a paramter named as service, then use CasSessionGuard, by libin 2018/03/09
        $service = $request->input('service','');
        if($service){
            $guard = config('auth.cas.guard');
        }

        if (Auth::guard($guard)->guest() || !Auth::guard($guard)->user()->enabled) {
            if (Auth::guard($guard)->user()) {
                Auth::guard($guard)->logout();
            }
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            }
            return redirect()->guest(cas_route('login.get'));
        }
        return $next($request);
    }
}
