<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model {

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ["ip_address", "time_blocked"];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['time_blocked' => 'datetime:Y-m-d H:i:s',];

}

?>
