<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\User;
use App\Model\Account;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckIfLoggedIn {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $headers = $request->headers->all();
        if (!empty($headers['access-token'][0])) {
            $access_token = $headers['access-token'][0];  #get token
            $user = User::where("web_session_id", $access_token)->first();

            if (is_null($user))
                return app(Controller::class)->sendResponse(400, 'invalid_token');
            $login_time = round(abs(strtotime(date('Y-m-d H:i:s')) - strtotime($user->web_last_activity)) / 60);
            #check if last activity more than 30 mins
            if ($login_time > 30) {
                return app(Controller::class)->sendResponse(400, 'session_timeout');
            } else {
                try {
                    DB::transaction(function () use ($user) {
                        $update_user = $user->update(['web_last_activity' => now(), 'modified' => now()]);
                    });
                } catch (\Exception $e) {
                    return app(Controller::class)->sendResponse(500, 'server_error');
                }
            }

            #check for storage data limit exhausted 
            $account_data = Account::with(['accountSubscription' => function($q) {
                                    $q->select(['id', 'account_id', 'storage_limit', 'storage_used', 'plan_code', 'refill_data_status', 'subscription_valid_upto']);
                                }])
                            ->where('id', $user->account_id)->select(['id', 'account_type'])->first();
            if (!is_null($account_data) && !is_null($account_data->accountSubscription)) {
                $storage_limit = ($account_data->accountSubscription->storage_limit > 0) ? ($account_data->accountSubscription->storage_limit / 1000) : 0;
                $storage_used = $account_data->accountSubscription->storage_used;

                if ($storage_used >= $storage_limit) {
                    if ($account_data->account_type == 'trial') {
                        return app(Controller::class)->sendResponse(400, 'storage_limit_reached_upgrade_account');
                    } else {
                        if ($account_data->accountSubscription->refill_data_status != 1 && in_array($account_data->accountSubscription->plan_code, config('constants.plan_code_array'))) {
                            return app(Controller::class)->sendResponse(400, 'storage_limit_reached_buy_storage');
                        }
                    }
                }
            }
        } else {
            return app(Controller::class)->sendResponse(400, 'token_not_found');
        }

        $request->user_data = $user->toArray();
        $response = $next($request);
        return $response;
    }

}
