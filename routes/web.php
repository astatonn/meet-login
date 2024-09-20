<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('{reactRoutes}', function () {
    return view('layouts.app');
    })->where('reactRoutes', '^((?!services|api).)*$'); // exceto 'api'