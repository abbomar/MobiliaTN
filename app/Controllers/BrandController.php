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
        return $this->responseSuccess($data);
    }

    public function getImage($brand_id)
    {

        $data = $this->brandModel->select("logo")->find($brand_id);
        if ( $data == null ) {
            return $this->fail();
        }


        $file =  './temp/' . Utils::generateRandomUUID() . '.png';
        file_put_contents($file, $data["logo"]);

        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }

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
