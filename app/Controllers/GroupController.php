<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Utils;
use App\Models\ClientModel;

class GroupController extends BaseController
{
    private $groupModel;

    public function __construct()
    {
        $this->groupModel = model('GroupModel');
    }

    public function index()
    {
        $data = $this->groupModel
            ->select('id, group_name, (select count(*) from users where users.group_id = groups.id) as users_count ')
            ->withDeleted()
            ->orderBy("group_name")
            ->findAll();

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

    public function getGroupUsers()
    {
        $clientModel = Model("ClientModel");

        $data = $clientModel
            ->select('id, phone_number, deleted_at')
            ->withDeleted()
            ->orderBy("phone_number")
            ->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }




    public function appendUsers($group_id) {

        if ( $this->groupModel->find($group_id) == null ) return $this->fail("We cannot find a group with this id");

        $data = $this->readParamsAndValidate([
            'content' => 'required|valid_base64',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }


        $usersArray = $this->getUsersFromCSVFile($data["content"]);

        if ( $usersArray == null )
            return $this->fail("Cannot parse file");


        $userModel = model('ClientModel');

        $totalRecords = count($usersArray);
        $validRecords = 0;

        //$userModel->db->transBegin();

        foreach ($usersArray as $user) {
            $row = [
                "phone_number" => $user["phone_number"],
                "full_name" => "import test",
                "group_id" => $group_id,
            ];

            try {
                if ( $userModel->insert($row) )
                    $validRecords ++;
            } catch(\Exception $e) {
            }
        }

        //$userModel->db->transComplete();

        return $this->responseSuccess(null, "$validRecords nouveaux utilisateurs créés sur $totalRecords lignes trouvées");
    }

    public function delete($group_id) {
        $clientModel = model('ClientModel');
        $clientModel->where("group_id",$group_id)->delete();

        $this->groupModel->delete($group_id);
        return $this->responseSuccess(null, "Le groupe $group_id a été bloqué avec success");
    }

    public function blockUsers($group_id) {

        if ( $this->groupModel->find($group_id) == null ) return $this->fail("We cannot find a group with this id");

        $data = $this->readParamsAndValidate([
            'content' => 'required|valid_base64',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }


        $usersArray = $this->getUsersFromCSVFile($data["content"]);

        if ( $usersArray == null )
            return $this->fail("Cannot parse file");


        $clientModel = model('ClientModel');

        $totalRecords = count($usersArray);
        $validRecords = 0;

        //$clientModel->db->transBegin();

        foreach ($usersArray as $user) {
            try {
                $clientModel->where("phone_number", $user["phone_number"])->delete();
                $validRecords ++;
            } catch(\Exception $e) {
            }
        }

        //$clientModel->db->transComplete();
        return $this->responseSuccess(null, "$validRecords nouveaux utilisateurs créés sur $totalRecords lignes trouvées");
    }





    public function getUsersFromCSVFile($base64)
    {
        $filename = 'temp/' . Utils::generateRandomUUID() . '.csv';
        if ( ! file_put_contents($filename, base64_decode( $base64, true)) )
        {
            return null;
        }

        $file = fopen($filename,"r");
        $res = array();

        while (($filedata = fgetcsv($file, null, ";")) !== FALSE) {

            $firstColumValue = trim($filedata[0]);

            if ( strlen($firstColumValue) == 8  )
            {
                $row = [
                    "phone_number" => "+216$firstColumValue",
                ];
                array_push($res, $row);
            }

        }
        fclose($file);

        //unlink($filename);

        return $res;

    }

}
