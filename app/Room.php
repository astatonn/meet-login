<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $model = 'penso_meet_rooms';
    protected $primaryKey = 'penso_meet_room_id';
    

    public static function createRoom($roomId)
    {

        //salvando no cache pois não tem banco
        if(\Cache::get('created_cache_time')){
            $createdAt = new \DateTime(\Cache::get('created_cache_time'));
            $now = new \DateTime();
            $diff = $createdAt->diff($now);
            if($diff->m > 0){
                \Cache::forget('rooms');
                \Cache::forget('created_cache_time');
            }
        }
        if(!\Cache::get('rooms')) {
            \Cache::rememberForever('created_cache_time', function() {
                return date('Y-m-d H:i:s');
        });
            \Cache::rememberForever('rooms',function () {
                return []; // Valor inicial, se não estiver no cache
            });
        }

        $rooms = \Cache::get('rooms');
        $rooms[$roomId] = [
            'room_id' => $roomId,
            'email' => auth()->user()->email,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        \Cache::put('rooms',$rooms);
 
/*        if(\DB::table('penso_meet_rooms')->insert([
            'room_id' => $roomId,
            'created_by' => auth()->user()->email,
            'created_at' => date('Y-m-d H:i:s'),
        ])){

        } */
    }

    public static function roomExists($roomId)
    {
        $rooms = \Cache::get('rooms');
        if(!$rooms) {
            return false;
        }
        return array_key_exists($roomId, $rooms);
//        return \DB::table('penso_meet_rooms')->where('room_id',$roomId)->first();
    }
     
    public static function getUserRooms()
    {
        return [];
    //    return \DB::table('penso_meet_rooms')->select('room_id','created_at')->where('created_by',auth()->user()->email)->orderBy('room_id','DESC')->limit(5)->get()->all();
    }

    public static function log($roomId)
    {
/*        if(auth()->check()){
            \Log::channel('meetRoomLog')->info(
                sprintf("%s - %s juntou-se a reunião - %s",auth()->user()->name, auth()->user()->email, $roomId),['username' => auth()->user()->name,'email' => auth()->user()->email, 'room' => $roomId]);
                return true;
        }
        \Log::channel('meetRoomLog')->info(
            sprintf("%s - juntou-se a reunião - %s",'Visitante', $roomId),['guest' => 'guest', 'room' => $roomId]);
            return true;

*/
    }
}
