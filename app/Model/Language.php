<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Language extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    const UPDATED_AT = 'modified';
    const CREATED_AT = 'created';
    

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
}
