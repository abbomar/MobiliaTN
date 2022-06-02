<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;
use Kreait\Firebase\Auth;
use Ovh\Api;

class TransactionController extends BaseController
{

    private $transactionModel;

    public function __construct()
    {
        $this->transactionModel = model('TransactionModel');
    }

    public function index($store_id)
    {
        $cashier = AuthenticationHelper::getConnectedUser($this->request);
        if ( $cashier["store_id"] != $store_id ) {
            return $this->failUnauthorized("You're not allowed to acces transactions of this store");
        }

        $data = $this->transactionModel
            ->select('id, total_amount, cash_amount, created_at as date')
            ->where("store_id", $store_id )
            ->findAll();

        return $this->responseSuccess($data);
    }


    public function initiateTransaction()
    {
        $cashier = AuthenticationHelper::getConnectedUser($this->request);
        if ( $cashier == null ) {
            return $this->failUnauthorized();
        }

        $data = $this->readParamsAndValidate([
            'client_phone_number' => 'required', // TODO : add constraint is not unique
            'registry_id' => 'required|is_not_unique[registries.id]', // TODO: add constraint is not unique
            'total_amount' => 'required|decimal',
            'cash_amount' => 'required|decimal|less_than_equal_to[total_amount]',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        /*if ( $data["total_amount"] < $data["cash_amount"] )
        {
            return $this->failValidationErrors(["total amount cannot be less than "]);
        }*/

        try{
            $client = Model('ClientModel')->select('user_id, balance_amount')->where('phone_number', $data["client_phone_number"])->first();
        } catch ( \Exception $e ) {
            return $this->failValidationErrors(["Cannot find a user with this id"]);
        }

        if ( $client["balance_amount"] < $data["total_amount"] - $data["cash_amount"] ) {
            return $this->failValidationErrors(["User don't have enough credits"]);
        }

        $data["status"] = "CREATED";
        $data["store_id"] = $cashier["store_id"];
        $data["cashier_id"] = $cashier["user_id"];
        $data["client_id"] = $client["user_id"];
        $data["otp"] = rand(100000,999999);
        unset($data["client_phone_number"]);

        $insertion_id = $this->transactionModel->insert($data);

        return $this->responseSuccess($insertion_id, "Transaction initiated successfully | OTP = {$data['otp']}" );
    }

    public function validateTransaction($transaction_id) {

        $data = $this->readParamsAndValidate([
            'otp' => 'required|exact_length[6]',
        ]);

        $transaction = $this->transactionModel
            ->select('id, otp, client_id, total_amount, cash_amount')
            ->where('status', 'CREATED')
            ->find($transaction_id);

        if ( $transaction == null ) {
            return $this->failValidationErrors(["We cannot find a created transaction with this id in this store"]);
        }

        $clientModel = Model('ClientModel');
        $client = $clientModel->select('balance_amount')->find($transaction["client_id"]);

        //TODO : Check integrity and lock tables

        if ( $client["balance_amount"] < $transaction["total_amount"] - $transaction["cash_amount"] ) {
            return $this->failValidationErrors(["User don't have enough credits"]);
        }

        if ( $transaction["otp"] != $data["otp"] ) {
            return $this->failValidationErrors(["Wrong OTP number"]);
        }


        // TODO: Add transactional SQL treatment
        $this->transactionModel->update($transaction_id, ["status" => "VALID"]);
        $clientModel->update($transaction["client_id"], ["balance_amount" => $client["balance_amount"] - $transaction["total_amount"] + $transaction["cash_amount"] ]);

        return $this->responseSuccess(null, "Transaction validated successfully");

    }

    public function testSMS()
    {
        $endpoint = 'ovh-eu';
        $applicationKey = "d3f7676b74054ee1";
        $applicationSecret = "fdbc04c9774da517b28a788ae6fe5001";
        $consumer_key = "182d18ff7eee619d4d282f5f79e423be";

        $conn = new Api(    $applicationKey,
            $applicationSecret,
            $endpoint,
            $consumer_key);

        $smsServices = $conn->get('/sms/');
        foreach ($smsServices as $smsService) {

            print_r($smsService);
        }

        $content = (object) array(
            "charset"=> "UTF-8",
            "class"=> "phoneDisplay",
            "coding"=> "7bit",
            "message"=> "Bonjour les SMS OVH par api.ovh.com",
            "noStopClause"=> false,
            "priority"=> "high",
            "receivers"=> [ "+21624509957" ],
            "senderForResponse"=> true,
            "validityPeriod"=> 2880
        );
        $resultPostJob = $conn->post('/sms/'. $smsServices[0] . '/jobs', $content);

        print_r($resultPostJob);

        $smsJobs = $conn->get('/sms/'. $smsServices[0] . '/jobs');
        print_r($smsJobs);
    }

    public function clientTransactionsHistory()
    {
        $client = AuthenticationHelper::getConnectedUser($this->request);
        if ( $client == null ) {
            return $this->failUnauthorized();
        }
        else {
            $client_id = $client["user_id"];
        }


        $data = $this->transactionModel
            ->join('stores','stores.id = transactions.store_id')
            ->select('transactions.id, store_name, total_amount, cash_amount, transactions.updated_at')
            ->where('status', 'VALID')
            ->where('client_id',  $client_id)
            ->findAll();

        return $this->responseSuccess($data);
    }

    public function cashierTransactionsHistory()
    {
        $cashier = AuthenticationHelper::getConnectedUser($this->request);
        if ( $cashier == null ) {
            return $this->failUnauthorized();
        }
        else {
            $cashier_id = $cashier["user_id"];
        }

        $data = $this->transactionModel
            ->join('stores','stores.id = transactions.store_id')
            ->select('transactions.id, store_name, total_amount, cash_amount, transactions.updated_at')
            ->where('status', 'VALID')
            ->where('client_id',  $cashier_id)
            ->findAll();

        return $this->responseSuccess($data);
    }


}
