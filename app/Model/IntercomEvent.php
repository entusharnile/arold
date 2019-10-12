<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class IntercomEvent extends Model {

    public $timestamps = false;

    public function checkIntercomEventStatus($event, $account_id, $admin_id) {
        $intercom_data = $this->where('account_id', $account_id)->where('admin_id', $admin_id)->select(['id', $event])->first();
        if (!is_null($intercom_data) && $intercom_data->$event == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function updateIntercomEventStatus($event, $account_id, $admin_id) {
        $intercom_data = $this->where('account_id', $account_id)->where('admin_id', $admin_id)->first();
        if (!is_null($intercom_data)) {
            $intercom_data->where('id', $intercom_data->id)->update([$event => 1]);
        } else {
            $insert_data = [
                'account_id' => $account_id,
                'admin_id' => $admin_id,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s')
            ];
            $this->create($insert_data);
        }
    }

}

?>
