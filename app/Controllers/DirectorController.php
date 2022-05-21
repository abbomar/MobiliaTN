<?php

namespace App\Controllers;

use App\Helpers\Utils;

class DirectorController extends BaseController
{
    private $directorModel;

    public function __construct()
    {
        $this->directorModel = model('DirectorModel');
    }

    // TODO: Only managers are allowed to make these operations

    public function index()
    {
        $data = $this->directorModel->select('user_id, phone_number, full_name, deleted_at')->withDeleted()->findAll();

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

        $this->directorModel->insert($data);

        return $this->responseSuccess(null, "Director created successfully");
    }

    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'full_name' => 'required',
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }

        if ( $this->directorModel->find($id) == null ) return $this->fail("We cannot find a director with this id");

        $this->directorModel->update($id, $data);

        return $this->responseSuccess(null, "Director updated successfully");
    }

    public function delete($id)
    {
        if ( $this->directorModel->find($id) == null ) return $this->fail("We cannot find a director with this id");

        $this->directorModel->delete($id);

        return $this->responseSuccess(null, "Director $id blocked successfully");
    }

}
