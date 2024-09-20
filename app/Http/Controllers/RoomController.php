<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use App\Classes\Jwt;
use App\Room;

class RoomController extends Controller
{

    public function enterRoom($roomId,Request $request)
    {
        if(!auth()->check() && !session()->get('guest.auth')){
            
            session()->put('url.roomUrl',"/".$roomId);
            
            return response()->json([],401);

        }

        $exists = Room::roomExists($roomId);
        if(!$exists){
            if(!auth()->check()){
                return response()->json([],403);
            }
            Room::createRoom($roomId);
        }
        return response()->json([
            'roomId'=>$roomId,
            'jwt'=>auth()->check() ? Jwt::generateToken($roomId) : false,
            'server' => env('JITSI_URL') ,
            'user' => auth()->check() ? [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email
            ] : false
            
    ]);

    }

    public function generateToken($roomId,Request $request)
    {
        if(!auth()->check()){
            abort(404);
        }

       return response()->json(['jwt' => Jwt::generateToken($roomId)]);
        
    }

    public function leaveRoom($roomId, Request $request)
    {
        if(\Cache::get("room.{$roomId}.active")){
            if( \Cache::get("room.{$roomId}.email") == $request->post('email')){
                \Cache::forget("room.{$roomId}.email");
                \Cache::forget("room.{$roomId}.active");
            }
        }
 
    }
    
    public function getAllRooms(Request $request)
    {
        if(!auth()->check()){
            abort(401);
        }
        $rooms = Room::getUserRooms();
        return response()->json(['status' => true, 'data' => $rooms]);
    }
}
