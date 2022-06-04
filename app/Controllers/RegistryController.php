<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;
use CodeIgniter\RESTful\ResourceController;

class RegistryController extends BaseController
{

    private $registryModel;

    public function __construct()
    {
        $this->registryModel = model('RegistryModel');
    }


    // TODO: Only managers can perform these opeations

    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index($store_id)
    {

        $data = $this->registryModel
            ->select('id, registry_name, deleted_at')
            ->where('store_id', $store_id)
            ->withDeleted()
            ->orderBy("full_name")
            ->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }


    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create($store_id)
    {
        $data = $this->readParamsAndValidate([
            'registry_name' => 'required',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $data["store_id"] = $store_id;

        $this->registryModel->insert($data);

        return $this->responseSuccess(null, "Registry created successfully");
    }


    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($store_id, $id = null)
    {
        $data = $this->readParamsAndValidate([
            'registry_name' => 'required|min_length[2]'
        ]);

        if( ! isset($data) ) {
            return $this->fail($this->validator->getErrors());
        }
        if ( $this->registryModel->find($id) == null || $this->registryModel->find($id)['store_id'] != $store_id )
            return $this->fail("We cannot find a registry with this id attached to this store");


        $this->registryModel->update($id, $data);

        return $this->responseSuccess(null, "Registry updated successfully");
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


    public function totalSumByDate($store_id, $registry_id)
    {
        $params = $this->readParamsAndValidate([
            'date' => 'required|valid_date'
        ]);

        if( ! isset($params) ) {
            return $this->fail($this->validator->getErrors());
        }

        $transactionModel = Model("TransactionModel");
        $data = $transactionModel
            ->select("sum(total_amount) sum")
            ->where("registry_id", $registry_id)
            ->where("store_id", $store_id)
            ->where('date(updated_at)', $params['date'])
            ->groupBy("date(updated_at)")
            ->where("status", "VALID")
            ->findAll();

        return $this->responseSuccess($data);
    }

    public function closeRegistry($store_id, $registry_id)
    {
        $params = $this->readParamsAndValidate([
            'date' => 'required|valid_date'
        ]);

        $transactionModel = Model("TransactionModel");

        $transactionsToBeConfirmed = $transactionModel
            ->select('id , "CONFIRMED" as status')
            ->where('date(updated_at)', $params['date'])
            ->where("registry_id", $registry_id)
            ->where("store_id", $store_id)
            ->where("status", "VALID")
            ->findAll();

        if ( count($transactionsToBeConfirmed) > 0 )
        {
            $transactionModel->updateBatch($transactionsToBeConfirmed, 'id');
        }

        return $this->responseSuccess(null, "Registry closed : {$params['date']} ");

    }

}
