<?php

namespace App\Models;

use CodeIgniter\Model;

class UserBlockModel extends Model
{
    protected $table = 'user_blocks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['blocker_id','blocked_id','created_at'];

    public function existsBetween(int $first, int $second): bool
    {
        return $this->groupStart()->where(['blocker_id'=>$first,'blocked_id'=>$second])->orWhere(['blocker_id'=>$second,'blocked_id'=>$first])->groupEnd()->countAllResults() > 0;
    }
}
