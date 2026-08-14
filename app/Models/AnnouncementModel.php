<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table = 'announcements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['created_by', 'type', 'title', 'content', 'target_path', 'recipient_count', 'created_at'];

    public function withAuthor(): self
    {
        return $this->select('announcements.*, users.username AS author_username')
            ->join('users', 'users.id = announcements.created_by', 'left');
    }
}
