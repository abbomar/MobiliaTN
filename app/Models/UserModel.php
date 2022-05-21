<?php

namespace App\Models;

use App\Helpers\Utils;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $useSoftDeletes   = true;
    protected $protectFields    = false;

    // Dates
    protected $useTimestamps = true;


    public function insert($data = null, bool $returnID = true)
    {
        $data['user_id'] =  Utils::generateRandomUUID();
        return parent::insert($data, $returnID); // TODO: Change the autogenerated stub
    }
}
