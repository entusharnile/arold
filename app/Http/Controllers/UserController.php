<?php

namespace App\Http\Controllers;

use App\User;
use App\Model\Account;
use App\Model\UserLog;
use App\Model\IntercomEvent;
use App\Model\SubscriptionUserLog;
use App\Traits\Intercom;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Libraries\SHAHasher;

class UserController extends Controller {

    use Intercom;

    /* get user list */

    public function index(Request $request) {
        $login_user = $request->user_data;
        $request_data = $request->all();
        $search_key = isset($request_data["search_key"]) ? trim($request_data["search_key"]) : "";
        $per_page_record = config("constants.items_per_page"); #records per page
        $default_userid = config("constants.default_user_id");

        $users = User::where('account_id', $login_user['account_id'])->where('status', 'Active')->where('id', '<>', $default_userid);
        if (!empty($search_key)) {
            $users->where(DB::raw("CONCAT(TRIM(firstname), ' ', TRIM(lastname))"), "LIKE", "%" . $search_key . "%");
        }
        $users = $users->paginate($per_page_record)->toArray();
        if (!empty($users['data']))
            return $this->sendResponse(200, trans("common.record_found"), $users);

        return $this->sendResponse(204, trans("common.record_not_found"));
    }

    /* add user */

    public function store(Request $request, User $user, IntercomEvent $intercom, UserLog $user_log) {
        $login_user = $request->user_data;
        $account_id = $login_user['account_id'];
        $request_data = $request->all();
        $roles = config("constants.roles");
        #validate data
        $rule = [
            'clinics_array' => 'required',
            'user_role_id' => 'required|in:1,2,3,4',
            'supplementary_title' => 'sometimes',
            'website' => 'sometimes',
            'user_image' => 'sometimes',
            'email_id' => 'required|email|unique:users,email_id',
            'password' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
            'pass_code' => 'required',
            'is_dashboard_enabled' => 'required',
            'clinic_id' => 'required',
            'contact_number_1' => 'sometimes',
            'contact_number_2' => 'sometimes',
            'contact_number_3' => 'sometimes',
            'contact_number_4' => 'sometimes',
            'email_id_2' => 'sometimes',
            'email_id_3' => 'sometimes',
            'address_line_1' => 'sometimes',
            'address_line_2' => 'sometimes',
            'address_line_3' => 'sometimes',
            'address_line_4' => 'sometimes',
            'pincode' => 'sometimes',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'is_md_consent_required' => 'sometimes',
            'md_user_id' => 'sometimes',
            'is_available_online' => 'sometimes',
            'is_provider' => 'sometimes',
            'monthly_procedure_goal' => 'sometimes',
            'weekly_procedure_goal' => 'sometimes',
            'monthly_sales_goal' => 'sometimes',
            'weekly_sales_goal' => 'sometimes',
            'bio_name' => 'sometimes',
            'bio_title' => 'sometimes',
            'bio_description' => 'sometimes',
        ];
        $validate_error = $this->validateData($request_data, $rule);
        if ($validate_error) {
            return $this->sendResponse(400, $validate_error);
        }

        $get_all_md = $user->where('user_role_id', 4)->where('status', 'Active')->where('account_id', $account_id)->get();
        #check user add limit reached or not
        $chk_limit = $this->chkUserAddLimit($login_user);
        if (!$chk_limit)
            return $this->sendResponse(400, 'add_user_limit_reached');

        $request_data['appointment_color'] = $this->getAppointmentColor($account_id);
        $request_data = $this->trimInputs($request_data);
        $user_format_data = $this->formatAddUserData($request_data);

        try {
            DB::transaction(function () use ($account_id, $intercom, $user_format_data, $login_user) {
//                dd("here");
//                $new_user = User::create($user_format_data); #save user data
                $account = Account::with(['accountSubscription' => function($q) {
                                $q->select('id', 'account_id', 'users_used', 'users_limit', 'plan_code', 'subscription_valid_upto', 'subscription_type', 'account_code');
                            }])->where('id', $account_id)->select('id', 'account_type')->first();
                            
                if (!is_null($account)) {
                    $this->updateUserUsedAndLimit($account, 'increase');

                    #connect db
                    $db_connect = $this->switchDatabase($account->database_name);
                    if (!$db_connect)
                        return $this->sendResponse(400, 'db_not_connected');
                    #insert user logs table
//                    $user_log->saveUserLogs($login_user->id, 'user', $new_user->id, 'add');
                }
            });
        } catch (\Exception $e) {
            return $this->sendResponse(500, 'server_error');
        }
    }

    /* check adding user limit */

    private function chkUserAddLimit($user) {
        $account_id = $user['account_id'];
        $plan_code_array = config('constants.plan_code_array');
        $account_data = Account::with(['accountSubscription' => function($q) {
                                $q->select('account_id', 'users_limit', 'plan_code');
                            }])
                        ->withCount(['users' => function($q) {
                                $q->where('status', 'Active');
                            }])
                        ->where('id', $account_id)->first();

        if (!is_null($account_data)) {
            $account_type = $account_data->account_type;
            $users_count = $account_data->users_count;
            $plan_code = $account_data->accountSubscription->plan_code;
            $users_limit = $account_data->accountSubscription->users_limit;

            if ($account_type == 'paid' && in_array($plan_code, $plan_code_array))
                return true;

            if ($users_count >= $users_limit)
                return false;
            return true;
        }
        return true;
    }

    /* format add user data */

    private function formatAddUserData($data) {
        /* 'user_image' => 'sometimes' */
        $data_array = [];
        $data_array['modified'] = now();
        $data_array['user_role_id'] = $data['user_role_id'];
        $data_array['clinic_id'] = $data['clinic_id'];
        $data_array['email_id'] = $data['email_id'];
        $data_array['password'] = (new SHAHasher)->make($data['password']);
        $data_array['firstname'] = $data['firstname'] ?? "";
        $data_array['lastname'] = $data['lastname'] ?? "";
        $data_array['pass_code'] = $data['pass_code'] ?? "";
        $data_array['website'] = $data['website'] ?? "";
        $data_array['is_dashboard_enabled'] = $data['is_dashboard_enabled'] ?? 0;
        $data_array['supplementary_title'] = $data['supplementary_title'] ?? "";
        $data_array['contact_number_1'] = $data['contact_number_1'] ?? "";
        $data_array['contact_number_2'] = $data['contact_number_2'] ?? "";
        $data_array['contact_number_3'] = $data['contact_number_3'] ?? "";
        $data_array['contact_number_4'] = $data['contact_number_4'] ?? "";
        $data_array['email_id_2'] = $data['email_id_2'] ?? "";
        $data_array['address_line_1'] = $data['address_line_1'] ?? "";
        $data_array['address_line_2'] = $data['address_line_2'] ?? "";
        $data_array['address_line_3'] = $data['address_line_3'] ?? "";
        $data_array['address_line_4'] = $data['address_line_4'] ?? "";
        $data_array['pincode'] = $data['pincode'] ?? "";
        $data_array['city'] = $data['city'] ?? "";
        $data_array['state'] = $data['state'] ?? "";
        $data_array['country'] = $data['country'] ?? "";
        $data_array['is_md_consent_required'] = $data['is_md_consent_required'] ?? 0;
        $data_array['md_user_id'] = $data['md_user_id'] ?? 0;
        $data_array['is_available_online'] = $data['is_available_online'] ?? "";
        $data_array['is_provider'] = $data['is_provider'] ?? 0;
        $data_array['monthly_procedure_goal'] = $data['monthly_procedure_goal'] ?? 0;
        $data_array['weekly_procedure_goal'] = $data['weekly_procedure_goal'] ?? 0;
        $data_array['monthly_sales_goal'] = $data['monthly_sales_goal'] ?? 0;
        $data_array['weekly_sales_goal'] = $data['weekly_sales_goal'] ?? 0;
        $data_array['bio_name'] = $data['bio_name'] ?? "";
        $data_array['bio_title'] = $data['bio_title'] ?? "";
        $data_array['bio_description'] = $data['bio_description'] ?? "";
        $data_array['appointment_color'] = $data['appointment_color'] ?? "";

        if ($data['user_role_id'] == 2)
            $data_array['is_provider'] = 0;
        if ($data['is_md_consent_required'] == 0)
            $data_array['md_user_id'] = 0;


        return $data_array;
    }

    /* get appointment unique color */

    private function getAppointmentColor($account_id) {
        $appointment_colors = config("constants.appointment_color");
        $used = User::where('user_role_id', 2)->where('account_id', $account_id)->distinct('appointment_color')->pluck('appointment_color')->toArray();
        if (!empty($used))
            $appointment_colors = array_diff($appointment_colors, $used);

        $color = array_random($appointment_colors) ?? '#4285F4';
        return $color;
    }

    /* update user used & limit */

    private function updateUserUsedAndLimit($data, $type, $user_id = null) {
        $account_type = $data->account_type;
        $user_used = $data->accountSubscription->users_used;
        $users_limit = $data->accountSubscription->users_limit;
        $plan_code = $data->accountSubscription->plan_code;
        $subscription_valid_upto = $data->accountSubscription->subscription_valid_upto;
        $subscription_type = $data->accountSubscription->subscription_type;
        $current_date = date("Y-m-d");

        $data->accountSubscription->users_used = $type == "increase" ? $user_used + 1 : $user_used - 1;

        if (in_array($plan_code, config('constants.plan_code_array'))) {
            if ($subscription_type == 'yearly')
                $start_month_date = date('Y-m-d', strtotime('-1 year', strtotime($subscription_valid_upto)));
            else
                $start_month_date = date('Y-m-d', strtotime('-1 month', strtotime($subscription_valid_upto)));

            $end_month_date = date("Y-m-d", strtotime($subscription_valid_upto));

            if ($type == 'increase') {
                $schedule_diff = date_diff(date_create($start_month_date), date_create($end_month_date));
                $schedule_days = (int) $schedule_diff->days;
                $days_to_decrease = date_diff(date_create($start_month_date), date_create($current_date));
                $days_to_decrease = (int) $days_to_decrease->days;
                $days_quota_added_for = $schedule_days - $days_to_decrease;
                if ($user_used > $users_limit) {
                    if ($account_type == 'paid') {
                        $data->accountSubscription->users_limit = $users_limit + 1;
                       echo "1"; dd($data);
                        #save account subscription data
                        $this->updateAccountUsesQuota($data, $days_quota_added_for, 'increase');
                    }
                }
            } else if ($type == 'decrease') {
                if (!empty($user_id)) {
                    $sub_user_log = SubscriptionUserLog::where('account_id', $data->id)->where('user_id', $user_id)->first();
                    if (!is_null($sub_user_log)) {
                        $created_date = $sub_user_log->created;

                        if (strtotime($created_date) <= strtotime($start_month_date))
                            $created_date = $start_month_date;

                        $schedule_diff = date_diff(date_create($created_date), date_create($end_month_date));
                        $schedule_days = (int) $schedule_diff->days;
                        $days_to_decrease = date_diff(date_create($created_date), date_create($current_date)); #
                        $days_to_decrease = (int) $days_to_decrease->days;
                        $days_quota_deleted_for = $schedule_days - $days_to_decrease;

                        $days_to_decrease = date_diff(date_create($start_month_date), date_create($current_date)); #
                        $users_limit = $users_limit - 1;
                        if ($accountType == 'paid') {
                            $data->accountSubscription->users_limit = $users_limit <= 1 ? 1 : $users_limit;
                            echo "1";dd($data);
                            #save account subscription data
                            $this->updateAccountUsesQuota($data, $days_quota_deleted_for, 'decrease');
                        }
                    }
                }
            }
        }
        return true;
    }

    private function updateAccountUsesQuota($data, $days_quota, $type) {
        $account_id = $data->id;
        $month_days = (int) date("t");
        $acc_sub_id = $data->accountSubscription->id;
        $users_limit = $data->accountSubscription->users_limit;
        $subscription_type = $data->accountSubscription->subscription_type;
        $account_code = $data->accountSubscription->account_code;
        $ar_plan = ($subscription_type == 'yearly') ? config('constants.ar_subscription_yearly') : config('constants.ar_subscription_monthly');

        $dataToSave = array();

        if (!strstr($account_code, 'cus_')) {
            $data->accountSubscription->storage_limit = (($ar_plan['storage_limit'] * 1000) * $users_limit) + $data->accountSubscription->add_on_storage;
            $data->accountSubscription->email_limit = ($subscription_type == 'yearly') ? ($ar_plan['email_limit'] * $users_limit) * 12 : ($ar_plan['email_limit'] * $users_limit);
            $data->accountSubscription->sms_limit = ($subscription_type == 'yearly') ? ($ar_plan['sms_limit'] * $users_limit) * 12 : ($ar_plan['sms_limit'] * $users_limit);
        } else {
            $storage_limit_per_day = ($ar_plan['storage_limit'] * 1000) / $month_days;
            $email_limit_per_day = $ar_plan['email_limit'] / $month_days;
            $sms_limit_per_day = $ar_plan['sms_limit'] / $month_days;

            $storage_limit_for_quota = floor($storage_limit_per_day * $daysQuotaAddedfor);
            $email_limit_for_quota = floor($email_limit_per_day * $daysQuotaAddedfor);
            $sms_limit_for_quota = floor($sms_limit_per_day * $daysQuotaAddedfor);

            $updated_storage_limit = ($type == 'increase') ? ($data->accountSubscription->storage_limit + $storage_limit_for_quota) : ($data->accountSubscription->storage_limit - $storage_limit_for_quota);
            $updated_email_limit = ($type == 'increase') ? ($data->accountSubscription->email_limit + $email_limit_for_quota) : ($data->accountSubscription->email_limit - $email_limit_for_quota);
            $updated_sms_limit = ($type == 'increase') ? ($data->accountSubscription->sms_limit + $sms_limit_for_quota) : ($data->accountSubscription->sms_limit - $sms_limit_for_quota);

            $data->accountSubscription->storage_limit = $updated_storage_limit;
            $data->accountSubscription->email_limit = $updated_email_limit;
            $data->accountSubscription->sms_limit = $updated_sms_limit;
        }

        #save account subscription data
        //$this->AccountSubscription->save($dataToSave);

        /* if (!strstr($account_code, 'cus_')) {
          App::import('Vendor', 'recurly', array('file' => 'recurly' . DS . 'lib' . DS . 'recurly.php'));
          Recurly_Client::$subdomain = RECURLY_SUB_DOMAIN;
          Recurly_Client::$apiKey = RECURLY_PRIVATE_KEY;
          try {
          if ($subscription_type == 'yearly') {
          $subscription_charges = (int) (($userLimit * ($ar_plan['price_per_user'] * 12)) * 100);
          } else {
          $subscription_charges = (int) (($userLimit * $ar_plan['price_per_user']) * 100);
          }
          $subscription = Recurly_Subscription::get($accountCount['AccountSubscription']['subscription_uuid']);
          $subscription->plan_code = $ar_plan['plan_code'];
          $subscription->unit_amount_in_cents = $subscription_charges;
          $subscription->updateImmediately();
          } catch (Exception $e) {
          $error_msg = $e->getMessage();
          return $this->output_response('error', $error_msg, '');
          exit;
          }
          } */
        return true;
    }

}
