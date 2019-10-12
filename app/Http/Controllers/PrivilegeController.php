<?php

namespace App\Http\Controllers;

use App\Model\Privilege;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PrivilegeController extends Controller {
    /* get all privileges */

    public function index() {
        $roles = config("constants.roles"); #get all roles

        $privileges = $this->checkCacheQuery('all_privileges');
        if (!$privileges)
            $privileges = Privilege::where('value', 1)->orderBy('user_role_id')->get()->toArray();
        $this->cacheQuery('all_privileges', $privileges, 60);

        $privilege_array = [];
        if (!empty($privileges)) {
            foreach ($privileges as $privilege) {
                $role = $roles[$privilege['user_role_id']];
                if (($privilege['parent'] == 0)) {
                    $last_id = $privilege['id'];
                    $last_sysname = $privilege['sysname'];
                    $privilege_array[$role][$privilege['sysname']] = [];
                } else {
                    if ($last_id == $privilege['parent']) {
                        $privilege_array[$role][$last_sysname][] = $privilege['sysname'];
                    }
                }
            }
        }
        if (!empty($privilege_array)) {
            $data = ['roles' => array_values($roles), 'permissions' => $privilege_array];
            return $this->sendResponse(200, 'record_found', $data);
        }
        return $this->sendResponse(204, 'record_not_found');
    }

}
