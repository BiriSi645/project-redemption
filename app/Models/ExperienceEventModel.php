<?php

namespace App\Models;

use CodeIgniter\Model;

class ExperienceEventModel extends Model
{
    protected $table = 'experience_events';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'event_type', 'source_key', 'points', 'created_at'];
}
