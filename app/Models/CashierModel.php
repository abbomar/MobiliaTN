<?php

namespace App\Models;


class CashierModel extends UserModel
{

    private $role = "CASHIER";

    public function  find($id = null)
    {
        parent::where("users.role",$this->role);
        return parent::find($id); // TODO: Change the autogenerated stub
    }

    public function findAll(int $limit = 0, int $offset = 0)
    {
        parent::where("users.role",$this->role);
        return parent::findAll($limit, $offset); // TODO: Change the autogenerated stub
    }

    public function insert($data = null, bool $returnID = true)
    {
        $data['role'] = $this->role;
        return parent::insert($data, $returnID);
    }


}
