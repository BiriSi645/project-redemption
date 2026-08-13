<?php

namespace App\Models;

use CodeIgniter\Model;

class DirectMessageModel extends Model
{
    protected $table = 'direct_messages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['conversation_id','sender_id','body','read_at','deleted_by_sender','deleted_by_recipient','created_at'];
    protected $validationRules = ['body' => ['label'=>'Mesaj','rules'=>'required|max_length[2000]']];
    protected $validationMessages = ['body'=>['required'=>'Boş mesaj gönderilemez.','max_length'=>'Mesaj en fazla 2000 karakter olabilir.']];
}
