<?php

namespace App\Models;

use App\Helpers\Utils;
use CodeIgniter\Model;

class RegistryModel extends Model
{
    protected $table            = 'registries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $insertID         = 0;
    protected $useSoftDeletes   = true;
    protected $protectFields    = false;

    protected $useTimestamps = true;

    public function insert($data = null, bool $returnID = true)
    {
        $data['id'] =  Utils::generateRandomUUID();
        return parent::insert($data, $returnID); // TODO: Change the autogenerated stub
    }
}
