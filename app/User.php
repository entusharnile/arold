<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Auth;

class User extends Authenticatable {

    use Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "clinic_id", "user_role_id", "is_provider", "account_id", "supplementary_title", "firstname", "lastname", "email_id", "password",
        "pass_code", "signature", "website", "contact_number_1", "contact_number_2", "contact_number_3", "contact_number_4", "email_id_2",
        "email_id_3", "address_line_1", "address_line_2", "address_line_3", "address_line_4", "pincode", "city", "state", "country", "user_image",
        "status", "session_id", "web_session_id", "last_login_ip", "last_login_date", "created", "modified", "password_flag", "monthly_procedure_goal",
        "weekly_procedure_goal", "monthly_sales_goal", "weekly_sales_goal", "is_md_consent_required", "timezone", "appointment_color",
        "vacation_mode", "vacation_startdate", "vacation_enddate", "last_activity", "web_last_activity", "remember_token", "bio_name", "bio_title", "bio_description",
        "gender", "dob", "is_available_online", "md_user_id", "show_signature_popup", "is_dashboard_enabled", "display_order", "is_demo"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_login_date' => 'datetime:Y-m-d H:i:s',
        'created' => 'datetime:Y-m-d H:i:s',
        'modified' => 'date:Y-m-d',
        'last_activity' => 'datetime:Y-m-d H:i:s',
        'web_last_activity' => 'datetime:Y-m-d H:i:s'
    ];

    /* Accessors */

    /* Get user image */

    public function getUserImageAttribute($value) {
        $default_account_storage = config("constants.default.account_storage_folder");
        if (!empty($default_account_storage)) {
            $path = storage_path() . "/media/" . $default_account_storage;
            if (!is_null($value) && !empty($value) && file_exists($path)) {
                if (file_exists($path . '/thumb_' . $value)) {
                    return $path . '/thumb_' . $value;
                } else if (file_exists($path . '/' . $value)) {
                    return $path . '/' . $value;
                }
            }
        }
        return storage_path() . "/default.png";
    }

    /* Get signature */

    public function getSignatureAttribute($value) {
        $default_account_storage = config("constants.default.account_storage_folder");
        if (!empty($default_account_storage) && !is_null($value) && !empty($value)) {
            $path = storage_path() . "/media/" . $default_account_storage . "/signatures";
            if (file_exists($path . "/" . $value)) {
                return $path . "/" . $value;
            }
        }
        return $value;
    }

    /* Get user role */

    public function getUserRoleIdAttribute($value) {
        $user_role_id = '';
        switch ($value) {
            case '1':
                $user_role_id = 'admin';
                break;
            case '2':
                $user_role_id = 'provider';
                break;
            case '3':
                $user_role_id = 'frontdesk';
                break;
            case '4':
                $user_role_id = 'md';
                break;
            default;
        }
        return $user_role_id;
    }

    /* End Accessors */


    /* Relationships */

    /* get user auth settings */

    public function userAuthSetting() {
        return $this->hasOne("App\Model\UserAuthSetting");
    }

    /*     * ** End Relationships *** */


    /*     * ** Custom functions *** */

    /* update multiple users by ids */

    public function updateMultipleUsersById(Array $ids = [], Array $updated_data = []) {
        return $this->whereIn('id', $ids)->update($updated_data);
    }

    /* update user by id */

    public function updateUserById($id, Array $data = []) {
        return $this->where('id', $id)->update($data);
    }

    /* End Custom functions */
}
