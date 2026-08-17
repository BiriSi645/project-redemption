<?php

namespace App\Models;

use App\Libraries\RealtimePublisher;
use CodeIgniter\Model;

class NoteModel extends Model
{
    protected $table            = 'notes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'user_id',
        'title',
        'content',
        'category',
        'is_public',
    ];

    protected $afterInsert = ['publishPublicNoteAfterInsert'];
    protected $afterUpdate = ['publishPublicNoteAfterUpdate'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id' => [
            'label' => 'Kullanıcı',
            'rules' => 'required|is_natural_no_zero',
        ],
        'title' => [
            'label' => 'Başlık',
            'rules' => 'required|min_length[2]|max_length[255]',
        ],

        'content' => [
            'label' => 'Not',
            'rules' => 'required|min_length[2]',
        ],

        'category' => [
            'label' => 'Kategori',
            'rules' => 'required|max_length[100]',
        ],

        'is_public' => [
            'label' => 'Görünürlük',
            'rules' => 'required|in_list[0,1]',
        ],
    ];


    protected function publishPublicNoteAfterInsert(array $eventData): array
    {
        if (! empty($eventData['result']) && (int) ($eventData['data']['is_public'] ?? 0) === 1) {
            (new RealtimePublisher())->broadcast('public-note', [
                'noteId' => (int) ($eventData['id'] ?? 0),
            ]);
        }

        return $eventData;
    }

    protected function publishPublicNoteAfterUpdate(array $eventData): array
    {
        if (! empty($eventData['result']) && array_key_exists('is_public', $eventData['data'])) {
            $ids = array_map('intval', (array) ($eventData['id'] ?? []));
            (new RealtimePublisher())->broadcast('public-note', [
                'noteId' => $ids[0] ?? 0,
            ]);
        }

        return $eventData;
    }

    public function getVisibleTo(int $userId, bool $isAdmin, string $search = '', string $category = '', string $scope = 'all', ?int $perPage = null): array
    {
        $builder = $this->select('notes.*, users.username AS owner_name')
            ->select('(SELECT COUNT(*) FROM note_comments WHERE note_comments.note_id = notes.id) AS comment_count', false)
            ->join('users', 'users.id = notes.user_id', 'left')
            ->orderBy('notes.created_at', 'DESC');

        if (! $isAdmin) {
            $builder->groupStart()
                ->where('notes.user_id', $userId)
                ->orWhere('notes.is_public', 1)
                ->groupEnd();
        }

        if ($scope === 'public') {
            $builder->where('notes.is_public', 1);
        } elseif ($scope === 'mine') {
            $builder->where('notes.user_id', $userId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('notes.title', $search)
                ->orLike('notes.content', $search)
                ->groupEnd();
        }

        if ($category !== '') {
            $builder->where('notes.category', $category);
        }

        return $perPage === null ? $builder->findAll() : $builder->paginate($perPage);
    }

    protected $validationMessages = [
        'title' => [
            'required'   => 'Başlık boş bırakılamaz.',
            'min_length' => 'Başlık en az 2 karakter olmalıdır.',
            'max_length' => 'Başlık en fazla 255 karakter olabilir.',
        ],

        'content' => [
            'required'   => 'Not içeriği boş bırakılamaz.',
            'min_length' => 'Not en az 2 karakter olmalıdır.',
        ],
    ];

    public function categoriesVisibleTo(int $userId, bool $isAdmin, string $scope = 'all'): array
    {
        $builder = $this->select('notes.category')->distinct()->orderBy('notes.category', 'ASC');

        if (! $isAdmin) {
            $builder->groupStart()
                ->where('notes.user_id', $userId)
                ->orWhere('notes.is_public', 1)
                ->groupEnd();
        }

        if ($scope === 'public') {
            $builder->where('notes.is_public', 1);
        } elseif ($scope === 'mine') {
            $builder->where('notes.user_id', $userId);
        }

        return array_column($builder->findAll(), 'category');
    }

    public function dashboardSummary(int $userId, bool $isAdmin): array
    {
        $builder = $this->select("SUM(CASE WHEN notes.user_id = {$userId} THEN 1 ELSE 0 END) AS own_count", false)
            ->select('SUM(CASE WHEN notes.is_public = 1 THEN 1 ELSE 0 END) AS public_count', false)
            ->select('COUNT(*) AS visible_count', false);

        if (! $isAdmin) {
            $builder->groupStart()
                ->where('notes.user_id', $userId)
                ->orWhere('notes.is_public', 1)
                ->groupEnd();
        }

        $summary = $builder->first() ?? [];

        return [
            'own'    => (int) ($summary['own_count'] ?? 0),
            'public' => (int) ($summary['public_count'] ?? 0),
            'visible'=> (int) ($summary['visible_count'] ?? 0),
        ];
    }

    public function latestVisibleTo(int $userId, bool $isAdmin, int $limit = 5): array
    {
        $builder = $this->select('notes.id, notes.user_id, notes.title, notes.is_public, notes.created_at, users.username AS owner_name')
            ->join('users', 'users.id = notes.user_id', 'left')
            ->orderBy('notes.created_at', 'DESC')
            ->limit($limit);

        if (! $isAdmin) {
            $builder->groupStart()
                ->where('notes.user_id', $userId)
                ->orWhere('notes.is_public', 1)
                ->groupEnd();
        }

        return $builder->findAll();
    }
}
