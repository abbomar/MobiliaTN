<?php

namespace App\Controllers;

use App\Helpers\Utils;

class ManagerController extends BaseController
{
    private $managerModel;

    public function __construct()
    {
        $this->managerModel = model('ManagerModel');
    }

    // TODO: Only partners or directors are allowed to make these operations

    public function index($store_id)
    {
        $data = $this->managerModel
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

        $this->managerModel->insert($data);

        return $this->responseSuccess(null, "Manager created successfully");
    }

    public function update($store_id, $id)
    {
        $data = $this->readParamsAndValidate([
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        if ( $this->managerModel->find($id) == null || $this->managerModel->find($id)['store_id'] != $store_id )
            return $this->fail("We cannot find a manager with this id attached to this store");


        $this->managerModel->update($id, $data);

        return $this->responseSuccess(null, "Manager updated successfully");
    }

    public function delete($store_id, $id)
    {
        if ( $this->managerModel->find($id) == null || $this->managerModel->find($id)['store_id'] != $store_id )
            return $this->fail("We cannot find a manager with this id attached to this store");

        $this->managerModel->delete($id);

        return $this->responseSuccess(null, "Manager $id blocked successfully");
    }

}
