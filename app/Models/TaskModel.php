<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table            = 'tasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'user_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'due_time',
        'completed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'title' => [
            'label' => 'Başlık',
            'rules' => 'required|min_length[2]|max_length[255]',
        ],
        'description' => [
            'label' => 'Açıklama',
            'rules' => 'permit_empty|max_length[5000]',
        ],
        'priority' => [
            'label' => 'Öncelik',
            'rules' => 'required|in_list[low,medium,high]',
        ],
        'status' => [
            'label' => 'Durum',
            'rules' => 'required|in_list[pending,completed]',
        ],
        'due_date' => [
            'label' => 'Son tarih',
            'rules' => 'permit_empty|valid_date[Y-m-d]',
        ],
        'due_time' => [
            'label' => 'Son saat',
            'rules' => 'permit_empty|regex_match[/^(?:[01]\\d|2[0-3]):[0-5]\\d$/]',
        ],
    ];

    protected $validationMessages = [
        'title' => [
            'required'   => 'Görev başlığı zorunludur.',
            'min_length' => 'Görev başlığı en az 2 karakter olmalıdır.',
            'max_length' => 'Görev başlığı en fazla 255 karakter olabilir.',
        ],
        'description' => [
            'max_length' => 'Açıklama en fazla 5000 karakter olabilir.',
        ],
        'priority' => [
            'in_list' => 'Geçerli bir öncelik seçin.',
        ],
        'due_date' => [
            'valid_date' => 'Geçerli bir son tarih seçin.',
        ],
        'due_time' => [
            'regex_match' => 'Geçerli bir son saat seçin.',
        ],
    ];

    public function getForUser(int $userId, string $status = 'all'): array
    {
        $builder = $this->where('user_id', $userId)
            ->orderBy("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END", '', false)
            ->orderBy('due_date IS NULL', 'ASC', false)
            ->orderBy('due_date', 'ASC')
            ->orderBy('created_at', 'DESC');

        if (in_array($status, ['pending', 'completed'], true)) {
            $builder->where('status', $status);
        }

        return $builder->findAll();
    }
}
