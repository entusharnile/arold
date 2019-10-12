<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;

class LanguageText extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'language_text';
    const UPDATED_AT = 'modified';
    const CREATED_AT = 'created';
    

    /* Relationships */

    /* get language */

    public function language() {
        return $this->belongsTo("App\Model\Language");
    }
    
    
}
