<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectSectionModel extends Model
{
    protected $table = 'project_sections';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['project_id', 'name', 'color', 'sort_order'];
}
