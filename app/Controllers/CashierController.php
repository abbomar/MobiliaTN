<?php

namespace App\Controllers;

use App\Helpers\Utils;

class CashierController extends BaseController
{
    private $cashierModel;

    public function __construct()
    {
        $this->cashierModel = model('CashierModel');
    }

    // TODO: Only managers are allowed to make these operations

    public function index($store_id)
    {
        $data = $this->cashierModel
            ->select('user_id, phone_number, full_name, deleted_at')
            ->where('store_id', $store_id)
            ->withDeleted()
            ->orderBy("full_name")
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

        $this->cashierModel->insert($data);

        return $this->responseSuccess(null, "Cashier created successfully");
    }

    public function update($store_id, $id)
    {

        $data = $this->readParamsAndValidate([
            'phone_number' => 'exact_length[12]|regex_match[\+216[0-9]{8}]|is_unique[users.phone_number]',
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        if ( $this->cashierModel->find($id) == null || $this->cashierModel->find($id)['store_id'] != $store_id )
            return $this->fail("We cannot find a cashier with this id attached to this store");


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
