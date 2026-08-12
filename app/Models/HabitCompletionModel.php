<?php

namespace App\Models;

use CodeIgniter\Model;

class HabitCompletionModel extends Model
{
    protected $table            = 'habit_completions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['habit_id', 'user_id', 'period_key', 'completed_on', 'completed_at'];
    protected $useTimestamps    = false;
}
