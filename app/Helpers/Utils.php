<?php

namespace App\Helpers;


use phpDocumentor\Reflection\Types\Array_;

class Utils
{
    public static function generateRandomUUID($trim = false)
    {

        $format = ($trim == false) ? '%04x%04x-%04x-%04x-%04x-%04x%04x%04x' : '%04x%04x%04x%04x%04x%04x%04x%04x';

        return sprintf($format,

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function replaceDeletedAt($data) {
        if ( is_array($data) )
        {
            foreach ($data as &$item)
            {
                $item['is_active'] = !isset($item['deleted_at']);
                unset($item['deleted_at']);
            }
        }
        else if ( is_object($data) ) {
            $data['is_active'] = !isset($data['deleted_at']);
            unset($data['deleted_at']);
        }

        return $data;
    }

}