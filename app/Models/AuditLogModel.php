<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['user_id', 'action', 'description', 'method', 'path', 'status_code', 'ip_address', 'user_agent', 'created_at'];
    protected $useTimestamps    = false;

    public function recent(int $limit = 10): array
    {
        return $this->select('audit_logs.*, users.username')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
