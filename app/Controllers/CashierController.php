<?php

namespace App\Controllers;

use App\Helpers\Utils;

class CashierController extends BaseController
{
    private $cashierModel;

    public function __construct()
    {
        $this->cashierModel = model('cashierModel');
    }

    // TODO: Only managers are allowed to make these operations

    public function index()
    {
        $data = $this->cashierModel->select('user_id, phone_number, full_name, deleted_at')->withDeleted()->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }

    public function create()
    {

        $data = $this->readParamsAndValidate([
            'phone_number' => 'required|exact_length[12]|regex_match[\+216[0-9]{8}]|is_unique[users.phone_number]',
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $this->cashierModel->insert($data);

        return $this->responseSuccess(null, "Cashier created successfully");
    }

    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        if ( $this->cashierModel->find($id) == null ) return $this->fail("We cannot find a cashier with this id");

        $this->cashierModel->update($id, $data);

        return $this->responseSuccess(null, "Cashier updated successfully");
    }

    public function delete($id)
    {
        if ( $this->cashierModel->find($id) == null ) return $this->fail("We cannot find a cashier with this id");

        $this->cashierModel->delete($id);

        return $this->responseSuccess(null, "Cashier $id blocked successfully");
    }

}
