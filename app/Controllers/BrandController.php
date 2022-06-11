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
        $data = $this->brandModel->select("id, brand_name, deleted_at")->findAll();
        $data = Utils::replaceDeletedAt($data);
        return $this->responseSuccess($data);
    }

    public function getImage($brand_id)
    {

        $data = $this->brandModel->select("logo")->find($brand_id);
        if ( $data == null ) {
            return $this->failNotFound();
        }

        $this->response->setHeader('Content-Type' , 'image/png; charset=utf-8');

        $base64 = $data["logo"];

        if ( $base64[0] == 'd' && $base64[1]=='a' ) {
            $data = explode( ',', $base64 );
            $this->response->setBody(base64_decode($data[1]));
        } else {
            $this->response->setBody(base64_decode($base64));
        }

        return $this->response;

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
