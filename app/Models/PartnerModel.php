<?php

namespace App\Models;

use App\Models\UserModel;

class PartnerModel extends UserModel
{

    public function insert($data = null, bool $returnID = true)
    {
        $data['role'] = 'PARTNER';
        return parent::insert($data, $returnID);
    }

}
