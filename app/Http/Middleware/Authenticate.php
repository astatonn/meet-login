<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        dd('ok');
       // if (! $request->expectsJson()) {
       //     return route('login');
       // }

       return response()->json(['status' => false, 'message' => 'Não autorizado'],403);
    }
}
