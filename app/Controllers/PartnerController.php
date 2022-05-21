<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;

class PartnerController extends BaseController
{
    private $partnerModel;

    public function __construct()
    {
        $this->partnerModel = model('partnerModel');
    }

    public function index()
    {
        /*if ( AuthenticationHelper::getRole($this->request) == null)
        {
            return $this->failUnauthorized();
        }*/

        $data = $this->partnerModel->select('user_id, phone_number, full_name, deleted_at')->withDeleted()->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }

    public function create()
    {
        if ( AuthenticationHelper::getRole($this->request) == null)
        {
            return $this->failUnauthorized();
        }

        $data = $this->readParamsAndValidate([
            'phone_number' => 'required|min_length[12]|is_unique[users.phone_number]',
            'full_name' => 'required|min_length[2]',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $this->partnerModel->insert($data);

        return $this->responseSuccess(null, "Partner created successfully");
    }

    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'full_name' => 'required|min_length[2]',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        $data['user_id'] = $id;

        //TODO: Check if this is really a partner


        $this->partnerModel->update($data['user_id'], $data);
        return $this->responseSuccess(null, "Partner updated successfully");
    }

    public function delete()
    {
        $data = $this->readParamsAndValidate([
            'user_id' => 'required|min_length[2]',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        $this->partnerModel->delete($data['phone_number']);
        return $this->responseSuccess(null, "Partner ${data['phone_number']} blocked successfully");

    }

}
