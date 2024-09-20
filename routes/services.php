<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Services Routes
|--------------------------------------------------------------------------
*/


Route::get('{roomId}/login',function(){
    return view('login.login-to-meet');
});
Route::post('/{roomId}/leave',[App\Http\Controllers\RoomController::class,'leaveRoom']);
Route::get('/', function () {
    if(auth()->check() || session()->get('guest.auth')){
        
        return view('dashboard.home');
    }
    return view('login.login');
});
Route::get('/login',function(){
    if(auth()->check()){ 
        return view('dashboard.home');
    }
    return view('login.login');
});
Route::post('/login',[App\Http\Controllers\LoginController::class,'login'])->name('login');

Route::post('/logout',[App\Http\Controllers\LoginController::class,'logout'])->name('logout');

Route::get('/get-user-rooms',[App\Http\Controllers\RoomController::class,'getAllRooms']);

Route::post('/guest',[App\Http\Controllers\LoginController::class,'guestLogin']);

Route::get('/{roomId}',[App\Http\Controllers\RoomController::class,'enterRoom']);

Route::post('/{roomId}/generate-token',[App\Http\Controllers\RoomController::class,'generateToken']);
