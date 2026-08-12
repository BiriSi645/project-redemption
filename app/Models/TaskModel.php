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
        'category',
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
        'category' => [
            'label' => 'Kategori',
            'rules' => 'required|max_length[100]',
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

    public function getForUser(int $userId, string $status = 'all', string $search = '', string $category = '', string $priority = '', ?int $perPage = null): array
    {
        $builder = $this->where('user_id', $userId)
            ->orderBy("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END", '', false)
            ->orderBy('due_date IS NULL', 'ASC', false)
            ->orderBy('due_date', 'ASC')
            ->orderBy('created_at', 'DESC');

        if (in_array($status, ['pending', 'completed'], true)) {
            $builder->where('status', $status);
        }

        if ($search !== '') {
            $builder->groupStart()->like('title', $search)->orLike('description', $search)->groupEnd();
        }

        if ($category !== '') {
            $builder->where('category', $category);
        }

        if (in_array($priority, ['low', 'medium', 'high'], true)) {
            $builder->where('priority', $priority);
        }

        return $perPage === null ? $builder->findAll() : $builder->paginate($perPage);
    }

    public function categoriesForUser(int $userId): array
    {
        return array_column(
            $this->select('category')->distinct()->where('user_id', $userId)->orderBy('category', 'ASC')->findAll(),
            'category'
        );
    }

    public function dashboardSummary(int $userId): array
    {
        $today = $this->db->escape(date('Y-m-d'));
        $summary = $this->select("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count", false)
            ->select("SUM(CASE WHEN status = 'pending' AND due_date = {$today} THEN 1 ELSE 0 END) AS due_today_count", false)
            ->where('user_id', $userId)
            ->first() ?? [];

        return [
            'pending' => (int) ($summary['pending_count'] ?? 0),
            'dueToday' => (int) ($summary['due_today_count'] ?? 0),
        ];
    }
}
