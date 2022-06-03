<?php

namespace App\Helpers;

use App\Models\UserModel;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Kreait\Firebase\Factory;


class AuthenticationHelper {

    private const FIREBASE_PROJECT_ID = "store-management-978fb";

    static public function getConnectedUser($request)
    {
        try {
            $jwt = str_replace("Bearer ","", $request->getHeaderLine("Authorization"));
            $auth = (new Factory)->withProjectId(self::FIREBASE_PROJECT_ID)->createAuth();
            $verifiedIdToken = $auth->verifyIdToken($jwt);

            $phone_number = $verifiedIdToken->claims()->get('phone_number');

            $user = (New UserModel())->select('user_id, full_name, balance_amount, phone_number, role, store_id, group_id')->where('phone_number', $phone_number)->findAll();


            if ( $user == null || count($user) == 0 ) return null;
            else {
                return $user[0];
            }
        } catch (\Throwable $e) {
            return null;
        }
    }

}
