<?php

namespace App\Classes;

class Jwt{


    public static function generateToken($room) : string
    {

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload = [ 
 
                "aud" => env('JITSI_ASAP_ACCEPETED_AUDIENCES'),
                "iss" => env('JITSI_ASAP_ACCEPTED_ISSUERS'),
                'sub' => env('JITSI_URL'),
                "room" => $room,
                "exp" => strtotime(date("Y-m-d H:i:s",strtotime('1 hour'))),
                "iat" => time(),
                'context' => [
                    "user" => [
                      //  'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'user_id' => auth()->user()->email,
                    ]
                ],
        ];
        $header = self::base64urlEncode(json_encode($header));
        $payload = self::base64urlEncode(json_encode($payload));
        $secret = env('JITSI_APP_SECRET');
        $signature = self::base64urlEncode(
            //hash_hmac('sha256',sprintf("%s.%s",$header,$payload),env('JITSI_APP_SECRET'),true)
            hash_hmac('sha256',sprintf("%s.%s",$header,$payload),$secret,true)

        );
        return sprintf("%s.%s.%s",$header,$payload,$signature);
    }

    public static function base64urlEncode(string $data) : string
    {
        return str_replace(["+","/","="],["-","_",""],base64_encode($data));
    }

}