<?php

namespace App\Models;

use CodeIgniter\Model;

class NoteMentionModel extends Model
{
    protected $table = 'note_mentions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['note_id','user_id','created_at'];
}
