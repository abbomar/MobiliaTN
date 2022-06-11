<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;
use Kreait\Firebase\Auth;

class DirectorController extends BaseController
{
    private $directorModel;

    public function __construct()
    {
        $this->directorModel = model('DirectorModel');
    }


    public function index()
    {
        $data = $this->directorModel
            ->select('user_id, phone_number, full_name, deleted_at')
            ->withDeleted()
            ->where("created_by", AuthenticationHelper::getConnectedUser($this->request)["user_id"])
            ->orderBy("full_name")
            ->findAll();

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

        $data["created_by"] = AuthenticationHelper::getConnectedUser($this->request)["user_id"];

        $this->directorModel->insert($data);

        return $this->responseSuccess(null, "Director created successfully");
    }

    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'phone_number' => 'exact_length[12]|regex_match[\+216[0-9]{8}]',
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        $director = $this->directorModel->withDeleted()->find($id);

        if ( $director == null  )
            return $this->fail("We cannot find a director with this id");

        if ( AuthenticationHelper::getConnectedUser($this->request)["user_id"] != $director["created_by"]  ) {
            return $this->failUnauthorized();
        }

        $userModel = Model("UserModel");
        if ( isset($data["phone_number"]) && $data["phone_number"] != $director["phone_number"] && count($userModel->withDeleted()->where("phone_number", $data["phone_number"])->findAll()) > 0 )
            return $this->failValidationErrors("Phone number already used by another user" , "PHONE_NUMBER_ALREADY_USED" );

        $this->directorModel->update($id, $data);

        return $this->responseSuccess(null, "Director updated successfully");
    }

    public function delete($id)
    {
        $director = $this->directorModel->withDeleted()->find($id);

        if ( $director == null ) return $this->fail("We cannot find a director with this id");

        if ( AuthenticationHelper::getConnectedUser($this->request)["user_id"] != $director["created_by"]  ) {
            return $this->failUnauthorized();
        }

        $this->directorModel->delete($id);

        return $this->responseSuccess(null, "Director $id blocked successfully");
    }

}
