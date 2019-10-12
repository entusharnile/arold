<?php

namespace App\Http\Middleware;

use Closure;
use Route;

class AfterMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        $currentAction = \Route::currentRouteAction();
        list($controller, $method) = explode('@', $currentAction);
        $controller = preg_replace('/.*\\\/', '', $controller);
        dd($response->getContent());
        // Perform action

        return $response;
    }
}
