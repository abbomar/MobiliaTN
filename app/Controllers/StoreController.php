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


    public function index()
    {
        $partner = AuthenticationHelper::getConnectedUser($this->request);

        $data = $this->storeModel
            ->select('id, store_name, deleted_at')
            ->withDeleted()
            ->where("partner_id", $partner["user_id"] )
            ->orderBy("store_name")
            ->findAll();

        $data = Utils::replaceDeletedAt($data);

        return $this->responseSuccess($data);
    }


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


    public function update($id)
    {
        $data = $this->readParamsAndValidate([
            'store_name' => 'required'
        ]);

        $store = $this->storeModel->withDeleted()->find($id);

        if (  $store == null ) {
            return $this->fail("We cannot find a store with this id");
        }

        if ( AuthenticationHelper::getConnectedUser($this->request)["user_id"] != $store["partner_id"]  ) {
            return $this->failUnauthorized();
        }


        $this->storeModel->update($id, $data);

        return $this->responseSuccess(null, "Store updated successfully");
    }



    public function stats($store_id)
    {

        $data = $this->readParamsAndValidate([
            'group_by' => 'required|in_list[day,week,month,year]',
        ]);

        if( ! isset($data) ) { return $this->fail($this->validator->getErrors()); }

        $store = $this->storeModel->withDeleted()->find($store_id);

        if ( AuthenticationHelper::getConnectedUser($this->request)["user_id"] != $store["partner_id"]  ) {
            return $this->failUnauthorized();
        }

        switch ($data["group_by"])
        {
            case "day":
                $sql_group_by = "DAY(updated_at), MONTH(updated_at), YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, '%d %M %Y')";
                break;
            case "week":
                $sql_group_by = "WEEK(updated_at, 7), YEAR(updated_at)";
                $format_date = "concat( DATE_FORMAT( STR_TO_DATE(DATE_FORMAT(updated_at, '%Y%v Monday'), '%x%v %W'), '%d/%m') , ' - ' , DATE_FORMAT( STR_TO_DATE(DATE_FORMAT(updated_at, '%Y%v Sunday'), '%x%v %W'), '%d/%m') ) ";
                break;
            case "month":
                $sql_group_by = "MONTH(updated_at), YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, '%M %Y')";
                break;
            case "year":
                $sql_group_by = "YEAR(updated_at)";
                $format_date = "DATE_FORMAT(updated_at, '%Y')";
                break;
        }

        $transactionModel = model("TransactionModel");

        $data = $transactionModel
            ->select("DATE_FORMAT(updated_at, $format_date ) as date ,  sum(total_amount) as total_amount")
            ->where("status", "CONFIRMED")
            ->where("store_id", $store_id)
            ->groupBy($sql_group_by)
            ->findAll();

        return $this->responseSuccess($data);
    }
}
