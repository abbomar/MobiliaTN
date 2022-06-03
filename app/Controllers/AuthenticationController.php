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

        if ( $user["role"] == "PARTNER" )
        {
            $transactionModel = Model("transactionModel");
            $sum = $transactionModel
                ->select("sum(total_amount) as sum")
                ->join("stores", "stores.id = transactions.store_id")
                ->where("stores.partner_id", $user["user_id"])
                ->where("transactions.status", "CONFIRMED")
                ->findAll();

            $user["turnover_amount"] =  $sum[0]["sum"] ?: 0;
        }



        return $this->responseSuccess($user);

    }
}
