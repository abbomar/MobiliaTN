<?php

namespace App\Controllers;

use App\Helpers\AuthenticationHelper;
use App\Helpers\Utils;
use CodeIgniter\Model;

class StoreController extends BaseController
{

    private $storeModel;

    public function __construct()
    {
        $this->storeModel = model('StoreModel');
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

        $data["partner_id"] = AuthenticationHelper::getConnectedUser($this->request)['user_id'];

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

    public function stats($store_id)
    {

        $data = $this->readParamsAndValidate([
            'group_by' => 'required|in_list[day,week,month,year]',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }


        switch ($data["group_by"])
        {
            case "day":
                $sql_group_by = "DAY(updated_at), MONTH(updated_at), YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, '%D %b %Y')";
                break;
            case "week":
                $sql_group_by = "WEEK(updated_at), YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, 'Semaine %U du %Y')";
                break;
            case "month":
                $sql_group_by = "MONTH(updated_at) + '-' + YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, '%b %Y')";
                break;
            case "year":
                $sql_group_by = "YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, '%Y')";
                break;
        }

        $transactionModel = model("transactionModel");

        $data = $transactionModel
            ->select("DATE_FORMAT(updated_at, $format_date ) as date ,  sum(total_amount) as total_amount")
            ->where("status", "CONFIRMED")
            ->where("store_id", $store_id)
            ->groupBy($sql_group_by)
            ->findAll();

        return $this->responseSuccess($data);

    }
}
