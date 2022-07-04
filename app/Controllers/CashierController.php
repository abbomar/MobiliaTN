<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;

class CashierController extends BaseController
{
    private $cashierModel;

    public function __construct()
    {
        $this->cashierModel = model('CashierModel');
    }


    public function index($store_id)
    {
        $data = $this->cashierModel
            ->select('users.user_id, users.phone_number, users.full_name, creator.full_name as created_by, users.created_at, users.deleted_at')
            ->join("users creator", "creator.user_id = users.created_by" , "left" )
            ->where('users.store_id', $store_id)
            ->withDeleted()
            ->orderBy("users.full_name")
            ->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }

    public function create($store_id)
    {
        $data = $this->readParamsAndValidate([
            'phone_number' => 'required|exact_length[12]|regex_match[\+216[0-9]{8}]|is_unique[users.phone_number]',
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $data['store_id'] = $store_id;
        $data['created_by'] = AuthenticationHelper::getConnectedUser($this->request)["user_id"];

        $this->cashierModel->insert($data);

        return $this->responseSuccess(null, "Cashier created successfully");
    }

    public function update($store_id, $id)
    {

        $data = $this->readParamsAndValidate([
            'phone_number' => 'exact_length[12]|regex_match[\+216[0-9]{8}]',
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        $cashier = $this->cashierModel->find($id);

        if ( $cashier == null || $this->cashierModel->withDeleted()->find($id)['store_id'] != $store_id )
            return $this->fail("We cannot find a cashier with this id attached to this store");

        $userModel = Model("UserModel");
        if ( isset($data["phone_number"]) && $data["phone_number"] != $cashier["phone_number"] && count($userModel->withDeleted()->where("phone_number", $data["phone_number"])->findAll()) > 0 )
            return $this->failValidationErrors("Phone number already used by another user" , "PHONE_NUMBER_ALREADY_USED" );


        $this->cashierModel->update($id, $data);

        return $this->responseSuccess(null, "Cashier updated successfully");
    }

    public function delete($store_id, $id)
    {
        if ( $this->cashierModel->find($id) == null || $this->cashierModel->find($id)['store_id'] != $store_id )
            return $this->fail("We cannot find a cashier with this id attached to this store");

        $this->cashierModel->delete($id);

        return $this->responseSuccess(null, "Cashier $id blocked successfully");
    }

}
