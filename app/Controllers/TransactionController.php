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


    public function initiateTransaction()
    {
        $cashier = AuthenticationHelper::getConnectedUser($this->request);
        if ( $cashier == null ) {
            return $this->failUnauthorized();
        }

        $data = $this->readParamsAndValidate([
            'client_phone_number' => 'required', // TODO : add constraint is not unique
            'registry_id' => 'required|is_not_unique[registries.id]',
            'total_amount' => 'required|decimal',
            'cash_amount' => 'required|decimal|less_than_equal_to[total_amount]',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        if ( $data["total_amount"] < $data["cash_amount"] )
        {
            return $this->failValidationErrors(["total amount cannot be less than "], "CASH_GREATER_THAN_TOTAL");
        }

        $client = Model('ClientModel')->select('user_id, balance_amount')->where('phone_number', $data["client_phone_number"])->first();

        if ( $client == null ) {
            return $this->failValidationErrors(["Cannot find a user with this phone number"], "CLIENT_NOT_FOUND");
        }

        if ( $client["balance_amount"] < $data["total_amount"] - $data["cash_amount"] ) {
            return $this->failValidationErrors(["User don't have enough credits"], "NOT_ENOUGH_CREDITS");
        }

        $data["status"] = "CREATED";
        $data["store_id"] = $cashier["store_id"];
        $data["cashier_id"] = $cashier["user_id"];
        $data["client_id"] = $client["user_id"];
        $data["otp"] = rand(100000,999999);

        $app_amount = $data["total_amount"] - $data["cash_amount"];

        //$this->sendSMS($data["client_phone_number"], "{$data["otp"]} est le code confidentiel de la transaction. Montant en espèces = {$data["cash_amount"]} DT. Montant app = {$app_amount} DT" ) ;

        Utils::sendSMS(AuthenticationHelper::getConnectedUser($this->request),
            "{$data["otp"]} est le code confidentiel de la transaction. Montant en espèces = {$data["cash_amount"]} DT. Montant app = {$app_amount} DT",
            $data["client_phone_number"], $data["otp"], "transaction-otp");

        unset($data["client_phone_number"]);

        $insertion_id = $this->transactionModel->insert($data);
        // TODO : remove otp from response when go on prod
        return $this->responseSuccess($insertion_id, "Transaction initiated successfully | OTP = {$data['otp']}",  );
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

    public function editTransaction($transaction_id) {

        $data = $this->readParamsAndValidate([
            'ref' => 'required',
        ]);


        $this->transactionModel->update($transaction_id, $data );

        return $this->responseSuccess(null, "Transaction updated successfully");

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
            ->where('status != ', 'CREATED')
            ->where('client_id',  $client_id)
            ->findAll();

        return $this->responseSuccess($data);
    }

    public function cashierTransactionsHistory()
    {
        $cashier = AuthenticationHelper::getConnectedUser($this->request);
        $cashier_id = $cashier["user_id"];

        $data = $this->transactionModel
            ->select('transactions.id, transactions.client_id, users.full_name, registries.registry_name, status, total_amount, cash_amount, transactions.created_at')
            ->join('stores','stores.id = transactions.store_id')
            ->join('users','users.user_id = transactions.client_id')
            ->join('registries','registries.id = transactions.registry_id')
            ->select('transactions.id, store_name, total_amount, cash_amount, transactions.updated_at')
            ->where('status !=', 'CREATED')
            ->where('cashier_id',  $cashier_id)
            ->findAll();

        return $this->responseSuccess($data);
    }


}
