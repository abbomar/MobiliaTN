<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\AuthenticationHelper;

class AuthenticationController extends BaseController
{
    public function me()
    {
        $user = AuthenticationHelper::getConnectedUser($this->request);

        if ( $user == null )
        {
            return $this->failUnauthorized();
        }

        return $this->responseSuccess($user);

    }
}
