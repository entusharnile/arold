<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model {

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "user_id", "object", "object_id", "action", "child", "child_id", "child_action", "created", "appointment_datetime", "merged_patients"
    ];

    /* Custom functions */

    /* save user logs data */

    public function saveUserLogs($user_id, $object, $object_id, $action, $child = NULL, $child_id = 0, $child_action = NULL, $app_datetime = NULL, $merged_patients = NULL) {
        $data = [
            'user_id' => $user_id,
            'object' => $object,
            'object_id' => $object_id,
            'action' => $action,
            'child' => $child,
            'child_id' => $child_id,
            'child_action' => $child_action,
            'created' => date('Y-m-d H:i:s'),
            'appointment_datetime' => $app_datetime,
            'merged_patients' => $merged_patients
        ];
        if ($this->create($data))
            return true;
        else
            return false;
    }

    /* End Custom functions */
}

?>
