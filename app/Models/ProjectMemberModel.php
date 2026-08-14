<?php
namespace App\Models;
use CodeIgniter\Model;
class ProjectMemberModel extends Model
{
    protected $table='project_members'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['project_id','user_id','invited_by','role','status','responded_at'];
}
