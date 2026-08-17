<?php

namespace App\Models;

use CodeIgniter\Model;

class CalendarReminderModel extends Model
{
    protected $table = 'calendar_reminders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'title', 'details', 'remind_at'];
}
