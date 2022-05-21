<?php

namespace App\Helpers;

use App\Models\UserModel;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Kreait\Firebase\Factory;


class AuthenticationHelper {

    private const FIREBASE_PROJECT_ID = "nacer-project";


    static public function getRoleFromToken($token)
    {
        if ( ! self::isTokenValid($token) ) return null;

    }

    static public function isTokenValid($token)
    {

    }

    static public function getRole($request)
    {
        try {
            $jwt = str_replace("Bearer ","", $request->getHeaderLine("Authorization"));
            $auth = (new Factory)->withProjectId(self::FIREBASE_PROJECT_ID)->createAuth();
            $verifiedIdToken = $auth->verifyIdToken($jwt);
            $phone_number = $verifiedIdToken->claims()->get('phone_number');

            $user = (new UserModel)->find($phone_number);
            if ( $user == null ) return null;
            else {
                return $user['role'];
            }

        } catch (\Throwable $e) {
            return null;
        }
    }

    static public function getConnectedUserId($request)
    {
        try {
            $jwt = str_replace("Bearer ","", $request->getHeaderLine("Authorization"));
            $auth = (new Factory)->withProjectId('nacer-project')->createAuth();
            $verifiedIdToken = $auth->verifyIdToken($jwt);
            $phone_number = $verifiedIdToken->claims()->get('phone_number');

            $user = (new UserModel)->find($phone_number);

            if ( $user == null ) return null;
            else {
                return $user['user_id'];
            }

        } catch (\Throwable $e) {
            return null;
        }
    }

    static public function parseToken($request)
    {
        $config = Configuration::forSymmetricSigner(
            new \Lcobucci\JWT\Signer\Rsa\Sha256(),
            \Lcobucci\JWT\Signer\Key\InMemory::base64Encoded('')
        );

        $jwt = $request->getHeaderLine("Authorization");

        $jwt =  str_replace("Bearer ","", $jwt);

        $token = $config->parser()->parse($jwt);

        assert($token instanceof UnencryptedToken);

        $token->headers();
        var_dump($token->claims());
    }

}
