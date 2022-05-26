<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Utils;

class GroupController extends BaseController
{
    private $groupModel;

    public function __construct()
    {
        $this->groupModel = model('GroupModel');
    }

    public function index()
    {
        $data = $this->groupModel->select('id, group_name')->withDeleted()->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }


    public function create()
    {
        $data = $this->readParamsAndValidate([
            'group_name' => 'required',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $this->groupModel->insert($data);

        return $this->responseSuccess(null, "Group created successfully");
    }

    public function appendUsers($group_id) {

        if ( $this->groupModel->find($group_id) == null ) return $this->fail("We cannot find a group with this id");


        $filename = 'temp/' . Utils::generateRandomUUID() . '.csv';
        file_put_contents($filename, base64_decode($this->request->getVar("content")));

        $file = fopen($filename,"r");

        $userModel = model('ClientModel');

        while (($filedata = fgetcsv($file, 500, ";")) !== FALSE) {
            $data = [
                "phone_number" => '+216' . $filedata[0],
                "full_name" => "import test",
                "group_id" => $group_id,
            ];
            $userModel->insert($data);
        }
        fclose($file);

        unlink($filename);
        return $this->responseSuccess(null);
    }


}
