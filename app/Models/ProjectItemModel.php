<?php
namespace App\Models;
use CodeIgniter\Model;
class ProjectItemModel extends Model
{
    protected $table='project_items'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['project_id','created_by','assigned_to','section_id','title','description','status','start_date','due_date'];
}
