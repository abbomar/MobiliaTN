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
        $user = AuthenticationHelper::getConnectedUser($this->request);

        $data = $this->storeModel
            ->select('id, store_name, users.full_name as created_by, stores.created_at, stores.deleted_at')
            ->join("users", "users.user_id = stores.created_by", "left")
            ->withDeleted()
            ->where("stores.created_by", $user["user_id"] )
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

        $connected_user = AuthenticationHelper::getConnectedUser($this->request);
        if ( $connected_user["role"] == "PARTNER" ) {
            $data["partner_id"] = $connected_user['user_id'];
        } else { // DIRECTOR
            $data["partner_id"] = $connected_user['created_by'];
        }

        $data["created_by"] = $connected_user["user_id"];

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


        $connected_user_id = AuthenticationHelper::getConnectedUser($this->request)["user_id"];

        if (  $connected_user_id != $store["created_by"] && $connected_user_id != $store["partner_id"] ) {
            return $this->failUnauthorized();
        }

        switch ($data["group_by"])
        {
            case "day":
                $sql_group_by = "DAY(created_at), MONTH(created_at), YEAR(created_at)";
                $format_date = "DATE_FORMAT(created_at, '%d %M %Y')";
                break;
            case "week":
                $sql_group_by = "WEEK(created_at, 7), YEAR(created_at)";
                $format_date = "concat( DATE_FORMAT( STR_TO_DATE(DATE_FORMAT(created_at, '%Y%v Monday'), '%x%v %W'), '%d/%m') , ' - ' , DATE_FORMAT( STR_TO_DATE(DATE_FORMAT(created_at, '%Y%v Sunday'), '%x%v %W'), '%d/%m') ) ";
                break;
            case "month":
                $sql_group_by = "MONTH(created_at), YEAR(created_at)";
                $format_date = "DATE_FORMAT(created_at, '%M %Y')";
                break;
            case "year":
                $sql_group_by = "YEAR(created_at)";
                $format_date = "DATE_FORMAT(created_at, '%Y')";
                break;
        }

        $transactionModel = model("TransactionModel");

        $data = $transactionModel
            ->select("DATE_FORMAT(created_at, $format_date ) as date ,  sum(total_amount) as total_amount")
            ->where("status", "CONFIRMED")
            ->where("store_id", $store_id)
            ->groupBy($sql_group_by)
            ->findAll();

        return $this->responseSuccess($data);
    }
}
