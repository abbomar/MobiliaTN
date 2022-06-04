<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Utils;

class BrandController extends BaseController
{

    private $brandModel;

    public function __construct()
    {
        $this->brandModel = model('BrandModel');
    }


    public function index()
    {
        $data = $this->brandModel->select("id, brand_name, logo, deleted_at")->findAll();
        $data = Utils::replaceDeletedAt($data);
        $this->responseSuccess($data);
    }

    public function create()
    {
        $data = $this->readParamsAndValidate([
            'brand_name' => 'required',
            'logo' => 'required|valid_base64',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $this->brandModel->insert($data);

        return $this->responseSuccess(null, "Brand created successfully");
    }

    public function update($brand_id){
        $data = $this->readParamsAndValidate([
            'brand_name' => 'required',
            'logo' => 'required|valid_base64',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }


        $this->brandModel->update($brand_id, $data);
        return $this->responseSuccess(null, "Brand updated successfully");
    }
}
