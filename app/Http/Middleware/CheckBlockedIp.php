<?php

namespace App\Http\Middleware;

use Closure;
use App\Model\BlockedIp;
use App\Http\Controllers\Controller;

class CheckBlockedIp {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $ip = $request->ip();
        $master_db = config("constants.db.master_db");
        $db_connect = app(Controller::class)->switchDatabase($master_db);
        if (!$db_connect)  #if db not connected
            return app(Controller::class)->sendResponse(400, 'db_not_connected');

        $is_blocked_ip = BlockedIp::where('ip_address', $ip)->first();
        if (!is_null($is_blocked_ip)) {
            $interval = abs(strtotime(date('Y-m-d H:i:s')) - strtotime($is_blocked_ip->time_blocked));
            if ($interval < 2 * 60 * 60)
                return app(Controller::class)->sendResponse(602, 'ip_blocked');
        }
        $response = $next($request);
        return $response;
    }

}
