<?php
namespace App\Classes\Helper;

class Helper {

    public static function decryptString($encrypted, $key, $bytes, $type = 'AES-256-CBC')
    {
        $string = openssl_decrypt(
            $encrypted,
            $type,
            $key,
            0,
            $bytes
        );
        return $string;
    }


}
