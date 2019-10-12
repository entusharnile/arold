<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Exception;
use Libraries\SHAHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Password Reset Controller
      |--------------------------------------------------------------------------
      |
      | This controller is responsible for handling password reset emails and
      | includes a trait which assists in sending these notifications from
      | your application to your users. Feel free to explore this trait.
      |
     */

use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request) {
        $request_data = $request->all();
        $rule = ['email' => 'required|email|exists:users,email_id'];
        $messages = [
            'email.required' => 'email_required',
            'email.email' => 'invalid_email',
            'email.exists' => 'email_does_not_exist'
        ];
        $validate_error = $this->validateData($request_data, $rule, $messages);
        if ($validate_error) {
            return $this->sendResponse(400, $validate_error);
        }

        $user = User::where('email_id', $request_data['email'])->select('id', 'email_id', 'firstname')->first();

        $generate_password = $this->generatePassword(10);
        $password = (new SHAHasher)->make($generate_password);

        /* 
        $send_data['user_id'] = $user->id;
        $send_data['password'] = $generate_password;
        $send_data['email'] = $request_data['email'];
        $send_data['firstname'] = ucfirst($request_data['firstname']);
        $send_data['subject'] = config("subjects.password_reset");
        $data['from'] = config("constants.FROM_EMAIL");
        //            $send_data['view'] = '';
//        $this->sendEmail('view', $send_data);
        /* */

        try {
            DB::transaction(function () use ($user, $password) {
                $user->update(['password' => $password, 'modified' => now()]);
            });
            return $this->sendResponse(200, 'password_sent_email', $generate_password);
        } catch (\Exception $e) {
            return $this->sendResponse(400, 'error_password_change');
        }
    }

}
