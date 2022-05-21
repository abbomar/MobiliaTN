<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;

class StoreController extends BaseController
{

    private $storeModel;

    public function __construct()
    {
        $this->storeModel = model('storeModel');
    }

    // TODO: Only directors / managers are allowed to execute these opearations

    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        // TODO: Filter to connected partner_id

        $data = $this->storeModel->select('id, store_name, deleted_at')->withDeleted()->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }


    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        $data = $this->readParamsAndValidate([
            'store_name' => 'required|min_length[2]',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $data["partner_id"] = AuthenticationHelper::getConnectedUserId($this->request);

        $this->storeModel->insert($data);

        return $this->responseSuccess(null, "Store created successfully");
    }


    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {

        $data = $this->readParamsAndValidate([
            'store_name' => 'required'
        ]);

        if ( $this->storeModel->find($id) == null ) return $this->fail("We cannot find a store with this id");

        $this->storeModel->update($id, $data);

        return $this->responseSuccess(null, "Store updated successfully");
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
}
