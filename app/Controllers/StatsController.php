<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class StatsController extends BaseController
{
    public function adminDetailsStats()
    {
        $params = $this->readParamsAndValidate([
            'group_by' => 'required|in_list[day,week,month,year]',
            'view' => 'required|in_list[partner,group]'
        ]);

        if( ! isset($params) ) { return $this->fail($this->validator->getErrors()); }


        switch ($params["group_by"])
        {
            case "day":
                $sql_group_by = "DAY(transactions.updated_at), MONTH(transactions.updated_at), YEAR(transactions.updated_at)";
                $format_date = "DATE_FORMAT(transactions.updated_at, '%D %b %Y')";
                break;
            case "week":
                $sql_group_by = "WEEK(transactions.updated_at), YEAR(transactions.updated_at)";
                $format_date = "DATE_FORMAT(transactions.updated_at, 'Semaine %U du %Y')";
                break;
            case "month":
                $sql_group_by = "MONTH(transactions.updated_at) + '-' + YEAR(transactions.updated_at)";
                $format_date = "DATE_FORMAT(transactions.updated_at, '%b %Y')";
                break;
            case "year":
                $sql_group_by = "YEAR(transactions.updated_at)";
                $format_date = "DATE_FORMAT(transactions.updated_at, '%Y')";
                break;
        }

        $transactionModel = model("TransactionModel");

        if ( $params["view"] == "partner"  ) {
            $data = $transactionModel
                ->select("DATE_FORMAT(transactions.updated_at, $format_date ) as date, partners.full_name as partner_name,  sum(total_amount) as total_amount")
                ->join('stores' , 'stores.id = transactions.store_id' )
                ->join('users as partners' , 'partners.user_id = stores.partner_id' )
                ->where("status", "CONFIRMED")
                ->groupBy($sql_group_by)
                ->groupBy("partners.full_name")
                ->findAll();

        } else {
            $data = $transactionModel
                ->select("DATE_FORMAT(transactions.updated_at, $format_date ) as date, groups.group_name,  sum(total_amount) as total_amount")
                ->join('users as clients' , 'clients.user_id = transactions.client_id' )
                ->join('groups' , 'groups.id = clients.group_id' )
                ->where("status", "CONFIRMED")
                ->groupBy($sql_group_by)
                ->groupBy("clients.group_id")
                ->findAll();
        }

        return $this->responseSuccess($data);
    }


    public function adminSummaryStats()
    {
        $transactionModel = model("TransactionModel");

        $data["current_month_sum"] = $transactionModel
            ->selectSum("total_amount")
            ->where("MONTH(updated_at) = MONTH(now())")
            ->where("YEAR(updated_at) = YEAR(now())")
            ->where("status", "CONFIRMED")
            ->find()[0]["total_amount"];




        return $this->responseSuccess($data);
    }


}
