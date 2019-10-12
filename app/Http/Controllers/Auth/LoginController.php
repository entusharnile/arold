<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Config;
use App\User;
use App\Model\Account;
use App\Model\UserPrivilege;
use App\Model\AccountPrivilege;
use App\Model\IntercomEvent;
use App\Traits\Intercom;
use Exception;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Login Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles authenticating users for the application and
      | redirecting them to your home screen. The controller uses a trait
      | to conveniently provide its functionality to your applications.
      |
     */

use AuthenticatesUsers,
    Intercom;

    private $language_id;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest')->except('logout');
        $this->language_id = config('constants.default_language_id');
    }

    /* user login manually */

    public function login(Request $request, User $user, IntercomEvent $intercom) {
        $ip = $request->ip();
        $request_data = $request->all();
        $roles = config("constants.roles");
        $min_login_attempt = config("constants.min_login_attempts");

        #validate data
        $validate_error = $this->validateLoginData($request_data);
        if ($validate_error) {
            return $this->sendResponse(400, $validate_error);
        }

        $wrong_login_attempts = !empty($request_data['login_attempts']) ? $request_data['login_attempts'] : 0;
        $email = $request_data['email'];
        $password = $request_data['password'];
        if (isset($request_data['is_need']))
            $is_need = $request_data['is_need'];

        #block ip if too many login attempts
        if ($wrong_login_attempts > $min_login_attempt) {
            try {
                DB::transaction(function () use ($ip) {
                    $this->blockIp($ip);
                });
            } catch (\Exception $e) {
                return $this->sendResponse(500, 'server_error');
            }
            return $this->sendResponse(400, 'wrong_attempts_ip_blocked');
        }



        if (Auth::attempt(['email_id' => $email, 'password' => $password, 'status' => 'Active'])) {

            #connect db
            $db_connect = $this->switchDatabase(config("constants.db.master_db"));
            if (!$db_connect)
                return $this->sendResponse(400, 'db_not_connected');

            $login_user = Auth::user(); # Get the currently authenticated user detail
            $login_user_id = Auth::id();
            $account_id = $login_user->account_id;
            $user_role_id = array_search($login_user->user_role_id, $roles);

            if (!isset($is_need)) {
                $hours = (StrToTime($login_user->web_last_activity) - StrToTime(date('Y-m-d H:i:s'))) * ( 60 * 60 ); #calculate login hours if logged in anywhere
                if (!empty($login_user->web_session_id) && $hours < 2)
                    return $this->sendResponse(601, 'already_logged_in');
            }

            $account_data = $this->getUserAccountDetails($account_id); #get account details of logged in user
            if (!is_null($account_data)) {
                $message = 'login_success';
                $user_type = $account_data->admin_id == $login_user_id ? 'admin' : 'user';
                $login_user->date_format = isset($account_data->accountPreference->date_format) ? $this->getDateFormat($account_data->accountPreference->date_format) : 'm/d/Y';
                $pos_enabled = 0;
                if ($account_data->pos_enabled == 1 && (($account_data->pos_gateway == 'rp' && $account_data->pos_client_id != '') || $account_data->pos_gateway == 'stripe' && !empty($account_data->accountStripeConfig)))
                    $pos_enabled = $account_data->pos_enabled;
                $account_data->pos_enabled = $pos_enabled;
                $account_status = $account_data->status;

                if ($account_status == "inactive") {
                    $message = $this->getAccountInactiveError($account_data->locked_reason, $user_type);
                    if ($user_type == 'user')
                        return $this->sendResponse(400, $message);
                }

                $today = date('Y-m-d');
                $hold_days = (strtotime($today) - strtotime(date('', strtotime($account_data->hold_on)))) / (60 * 60 * 24);
                $days_left_for_suspension = config('constants.grace_period') - $hold_days;
                $suspension_date = date('m/d/Y', strtotime("+$days_left_for_suspension days"));
                $subscription_valid_upto = $account_data->accountSubscription->subscription_valid_upto;
                #generate access token
                $access_token = $this->generateRandomString(20) . config('constants.security_salt') . $account_id;
                $access_token = md5($access_token);
                try {
                    DB::transaction(function () use ($user, $intercom, $login_user, $user_type, $account_id, $login_user_id, $ip, $access_token) {
                        #intercom event tracking function call
                        if (is_null($login_user->last_login_date) || empty($login_user->last_login_date)) {
                            if ($user_type == 'admin') {
                                if ($intercom->checkIntercomEventStatus('first_login', $account_id, $login_user_id)) {
                                    $this->createObject('events', ['event_name' => 'First Login', 'created_at' => time(), 'user_id' => $login_user_id]);
                                    $intercom->updateIntercomEventStatus('first_login', $account_id, $login_user_id);
                                }
                            }
                        }

                        $updated_data = array(
                            'last_login_ip' => $ip,
                            'last_login_date' => date('Y-m-d H:i:s'),
                            'web_last_activity' => date('Y-m-d H:i:s'),
                            'web_session_id' => $access_token,
                            'modified' => now()
                        );
                        $update_user = $user->updateUserById($login_user_id, $updated_data);
                    });
                } catch (\Exception $e) {
                    return $this->sendResponse(500, 'server_error');
                }

                if ($user_type == 'admin') {
                    #force upgrade account plan to new ar plan and cc details 
                    $force_upgrade_flag = config("constants.force_upgrade_flag");
                    if ($force_upgrade_flag == 1 && $account_data->account_type == 'paid' && $account_data->accountSubscription->subscription_type == 'monthly' && !strstr($account_data->accountSubscription->account_code, 'cus_') && $account_data->is_test_account == 0) {
                        $message = trans('auth.login.upgrade_account_to_stripe');
                    }
                    if ($account_status == 'cancelled' && $subscription_valid_upto >= $today) {
                        $days = (strtotime($subscription_valid_upto) - strtotime($today)) / (60 * 60 * 24);
                        $days_string = $days > 1 ? 'days' : 'day';
                        $message = 'account_suspend_reactivate_subscription';
                        $data = ['days' => $days, 'days_string' => $days_string];
                    } else if ($account_status == 'hold' && $hold_days > 0) {
                        $days = (strtotime($today) - strtotime($hold_on)) / (60 * 60 * 24);
                        $days_string = $days > 1 ? 'days' : 'day';
                        if (strstr($account_data->accountSubscription->account_code, 'cus_') && !empty($account_data->account_subscription_invoice))
                            $message = 'payment_failure_account_onhold';
                        else
                            $message = 'sub_limit_reach_account_onhold';

                        $data = ['days_left' => $days_left_for_suspension, 'days' => $days_string, 'suspension_date' => $suspension_date];
                    }
                }
                $user_privileges = $this->getUserPrivileges($login_user_id, $account_id, $user_role_id);
                $data['user'] = $login_user;
                $data['account'] = $account_data;
                $data['permissions'] = $user_privileges;
                $headers = ['access-token' => $access_token];
                return $this->sendResponse(200, $message, $data, $headers);
            }
        }
        return $this->sendResponse(400, 'invalid_credentials');
    }

    /* Validate login data */

    private function validateLoginData($request_data) {
        $rule = [
            'email' => 'required|email|exists:users,email_id,status,Active',
            'password' => 'required',
            'is_need' => 'sometimes|in:1'
        ];
        $messages = [
            'email.required' => 'email_required',
            'email.email' => 'invalid_email',
            'email.exists' => 'email_does_not_exist',
            'password.required' => 'password_required',
            'is_need.in' => 'invalid_is_need',
        ];
        return $this->validateData($request_data, $rule, $messages);
    }

    /*  Get user account data */

    private function getUserAccountDetails($account_id) {
        $account_data = Account::with(['accountSubscription', 'accountPreference', 'accountStripeConfig',
                            'accountSubscriptionInvoice' => function($q) {
                                $q->where('payment_status', 'past_due')->orderBy('id', 'desc');
                            }])
                        ->where('id', $account_id)->first();
        return $account_data;
    }

    /*  Get account inactive reason/error */

    private function getAccountInactiveError($locked_reason, $user_type) {
        $message = '';
        switch ($locked_reason) {
            case 'canceled_expired':
                $message = ($user_type == 'admin') ? 'subscription_cancelled' : 'subscription_cancelled_by_admin';
                break;
            case 'trial_expired':
                $message = ($user_type == 'admin') ? 'trial_subscription_expired' : 'trial_subscription_expired_contact_admin';
                break;
            case 'hold_expired':
                $message = ($user_type == 'admin') ? 'payment_failure_account_locked' : 'payment_failure_account_locked_contact_admin';
                break;
            case 'datastorage_exhausted':
                $message = ($user_type == 'admin') ? 'storage_limit_reached_buy_storage' : 'storage_limit_reached_contact_admin';
                break;
            default:
                $message = 'account_locked_contact_administrator';
        }
        return $message;
    }

    /* Get loggedin user privileges */

    private function getUserPrivileges($user_id, $account_id, $user_role_id) {
        $roles = config("constants.roles"); #get all roles
        $privilege_array = [];

        #fetch user privileges table data
        $user_privileges = $this->checkCacheQuery('user_privileges');
        if (!$user_privileges)
            $user_privileges = UserPrivilege::where('user_id', $user_id)->where('account_id', $account_id)->get(['sysname'])->toArray();
        $this->cacheQuery('user_privileges', $user_privileges, 5);

        if (!empty($user_privileges)) {
            $user_privileges = array_column($user_privileges, 'sysname');
            #fetch account privileges table data
            $account_privileges = AccountPrivilege::where('account_id', $account_id)->where('user_role_id', $user_role_id)->get()->toArray();
            if (!empty($account_privileges)) {
                foreach ($account_privileges as $privilege) {
                    $role = $roles[$privilege['user_role_id']];
                    if (($privilege['parent'] == 0)) {
                        $last_id = $privilege['id'];
                        $last_sysname = $privilege['sysname'];
                        $privilege_array[$privilege['sysname']] = [];
                    } else {
                        if ($last_id == $privilege['parent']) {
                            if (in_array($privilege['sysname'], $user_privileges)) {
                                $privilege_array[$last_sysname][] = $privilege['sysname'];
                            }
                        }
                    }
                }
            }
        }
        return $privilege_array;
    }

}
