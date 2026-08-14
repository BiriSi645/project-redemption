<?php
namespace App\Models;
use CodeIgniter\Model;
class ProjectModel extends Model
{
    protected $table='projects'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['owner_id','name','description','color'];
}
