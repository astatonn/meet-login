<?php

namespace App\Classes;

class Room{

    public static function verifyRoomModerator($roomId)
    {
        if(\Cache::get(sprintf("room.%s.active",$roomId))){
            return true;
        }
        return false;
    }

    public static function addRoomModerator($roomId)
    {
        \Cache::put(sprintf('room.%s.active',$roomId),true);
        \Cache::put(sprintf('room.%s.email',$roomId),auth()->user()->email);
    }

}