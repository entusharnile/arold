<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Account extends Model {

    public $timestamps = false;

    /* Relationship */

    /* get account stripe configuration */

    public function accountStripeConfig() {
        return $this->hasOne('\App\Model\AccountStripeConfig');
    }

    /* get account stripe preference */

    public function accountPreference() {
        return $this->hasOne('\App\Model\AccountPrefrence');
    }
    
    /* get account stripe subscription */

    public function accountSubscription() {
        return $this->hasOne('\App\Model\AccountSubscription');
    }
    
    /* get account subscription invoice */

    public function accountSubscriptionInvoice() {
        return $this->hasOne('\App\Model\SubscriptionInvoice');
    }
    
    /* get users */

    public function users() {
        return $this->hasMany('\App\User');
    }

}

?>
