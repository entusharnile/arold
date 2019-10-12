<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\Controller;

class AllowedIp {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $ip = $request->ip();
        if (!in_array($ip, config("constants.allowed_ips")))
            return app(Controller::class)->sendResponse(602, 'ip_blocked');

        $response = $next($request);
        return $response;
    }

}
