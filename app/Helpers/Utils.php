<?php

namespace App\Helpers;


use Ovh\Api;
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


    public static function sendSMS($user, $body, $phone_number, $otp=0, $type="")
    {

        $smsModel = model('SMSModel');

        $data = [
            'user_id' => $user['user_id'],
            'body' => $body,
            'phone_number' => $phone_number,
            'otp' => $otp,
            'status' => "0",
            "type" => $type
        ];


        $sms_id = $smsModel->insert($data);


        $endpoint = 'ovh-eu';
        $applicationKey = "092d82f35c77c5d8";
        $applicationSecret = "96140a4414de0a2e078d1a587dfddbdd";
        $consumer_key = "1d614f36c2eacd1572088cfd860a41aa";

        $conn = new Api(    $applicationKey,
            $applicationSecret,
            $endpoint,
            $consumer_key);

        $smsServices = $conn->get('/sms');

        $smsSenders = $conn->get('/sms/' . $smsServices[0] . '/senders' );


        $content = (object) array(
            "charset"=> "UTF-8",
            "class"=> "phoneDisplay",
            "coding"=> "7bit",
            "message"=> $body,
            "noStopClause"=> false,
            "priority"=> "high",
            "receivers"=> [ $phone_number ],
            "sender" => "TUNTRANSACT",
            "validityPeriod"=> 2880
        );
        $resultPostJob = $conn->post('/sms/'. $smsServices[0] . '/jobs', $content);

        $smsJobs = $conn->get('/sms/'. $smsServices[0] . '/jobs');


        return $sms_id;
    }

}