<?php 
namespace Libraries;

use Libraries\SHAHasher;
use Illuminate\Hashing\HashServiceProvider;

class SHAHashServiceProvider extends HashServiceProvider
{
    public function register()
    {
        $this->app->singleton('hash', function() { return new ShaHasher; });
    }
}