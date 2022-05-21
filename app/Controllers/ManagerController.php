<?php

namespace App\Controllers;

use App\Helpers\Utils;

class ManagerController extends BaseController
{
    private $managerModel;

    public function __construct()
    {
        $this->managerModel = model('managerModel');
    }

    // TODO: Only partners or directors are allowed to make these operations

    public function index()
    {
        $data = $this->managerModel->select('user_id, phone_number, full_name, deleted_at')->withDeleted()->findAll();

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

        $this->managerModel->insert($data);

        return $this->responseSuccess(null, "Manager created successfully");
    }

    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        if ( $this->managerModel->find($id) == null ) return $this->fail("We cannot find a manager with this id");

        $this->managerModel->update($id, $data);

        return $this->responseSuccess(null, "Manager updated successfully");
    }

    public function delete($id)
    {
        if ( $this->managerModel->find($id) == null ) return $this->fail("We cannot find a manager with this id");

        $this->managerModel->delete($id);

        return $this->responseSuccess(null, "Manager $id blocked successfully");
    }

}
