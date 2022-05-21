<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;
use App\Models\PartnerModel;

class PartnerController extends BaseController
{
    private $partnerModel;

    public function __construct()
    {
        $this->partnerModel = model('partnerModel');
    }

    // TODO: Only admins are allowed to make these operations

    public function index()
    {
        $data = $this->partnerModel->select('user_id, phone_number, full_name, deleted_at')->withDeleted()->findAll();

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

        $this->partnerModel->insert($data);

        return $this->responseSuccess(null, "Partner created successfully");
    }

    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        if ( $this->partnerModel->find($id) == null ) return $this->fail("We cannot find a partner with this id");

        $this->partnerModel->update($id, $data);

        return $this->responseSuccess(null, "Partner updated successfully");
    }

    public function delete($id)
    {
        if ( $this->partnerModel->find($id) == null ) return $this->fail("We cannot find a partner with this id");

        $this->partnerModel->delete($id);

        return $this->responseSuccess(null, "Partner $id blocked successfully");
    }

}
