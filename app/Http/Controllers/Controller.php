<?php

namespace App\Http\Controllers;

//use Illuminate\Foundation\Bus\DispatchesJobs;
//use Illuminate\Foundation\Validation\ValidatesRequests;
//use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Config;
use Exception;
use Validator;
use DateTime;
use App\Model\BlockedIp;
use App\Model\LanguageText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Controller extends BaseController {

    //use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /* switch database connection */

    public function switchDatabase($user_db = null) {
        if ($user_db != null) {
            $config = Config::set("database.connections.mysql.database", $user_db);
        } else {
            $user_db = config("constants.db.master_db");
            $config = Config::set("database.connections.mysql.database", $user_db);
        }
        Config::set("database.default", "mysql");
        try {
            DB::reconnect("mysql");
//            Config::set("constants.db.current_selected_db", $user_db);
            return true;
        } catch (\Exception $e) {
            return false;
        }
        //echo config("database.connections.mysql.database");die;
        //dd(\DB::connection("mysql"));
    }

    /* validate data */

    public function validateData($validator, Array $rule, Array $message = []) {
        $validator = Validator::make($validator, $rule, $message);
        if ($validator->fails()) {
            $errors = $validator->errors()->messages();
            if (!empty($errors)) {
                foreach ($errors as $key => $err) {
                    return $err[0];
                }
            }
        }
        return false;
    }

    /* get date format */

    public function getDateFormat($date) {
        switch ($date) {
            case 'yyyy/mm/dd':
                $return = 'Y/m/d';
                break;
            case 'dd/mm/yyyy':
                $return = 'd/m/Y';
                break;
            case 'mm/dd/yyyy':
                $return = 'm/d/Y';
                break;
            default:
                $return = 'm/d/Y';
                break;
        }
        return $return;
    }

    /* cache query */

    public function cacheQuery($key, $query, $timeout = 5) {
        return Cache::remember($key, $timeout, function() use ($query) {
                    return $query;
                });
    }

    /* generate random string */

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /* check cache query & return result */

    public function checkCacheQuery($key) {
        if (Cache::has($key)) {
            return Cache::get($key);
        }
        return false;
    }

    /* block ip */

    public function blockIp($ip) {
        return BlockedIp::updateOrCreate(["ip_address" => $ip], ["time_blocked" => now()]);
    }

    /* send response */

    public function sendResponse($status, $msg, $data = null, $header = []) {
        $http_status = $status;
        if (!in_array($status, config("constants.status_codes")))
            $http_status = 400;
        $response = response()->json(['status' => $status, 'message' => $msg, 'data' => $data], $http_status);
        if (!empty($header))
            $response->withHeaders($header);
        return $response;
    }

    /* trim input data */

    public function trimInputs(Array $data) {
        foreach ($data as $key => $value) {
            
        }
        return $data;
    }

    /* Generate password */

    public function generatePassword($length = 10) {
        // initialiaz variables 
        $password = "";
        $i = 0;
        $possible = "0123456789abcdefghijklmnopqrstuvwxyz[]*()=_-+#@!";
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }

    /** Send Email */
    public function sendEmail($data) {
        $data['from'] = config("constants.FROM_EMAIL");
        try {
            Mail::send($data['view'], $data, function ($message) use ($data) {
                $message->view($data['view'])
                        ->from($data['from'], $data['from_name'])
                        ->to($data['to'])
                        ->replyTo($data['from'], $data['from_name'])
                        ->subject($data['subject'])
                        ->with(['message' => $this->data['message']]);
            });
        } catch (\Exception $ex) {
            return false;
        }
        if (count(Mail::failures()) > 0) {
            return false;
        } else {
            return true;
        }
    }

}
