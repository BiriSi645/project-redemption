<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalEntryModel extends Model
{
    protected $table            = 'journal_entries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'user_id',
        'entry_date',
        'title',
        'content',
        'mood',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'entry_date' => [
            'label' => 'Tarih',
            'rules' => 'required|valid_date[Y-m-d]',
        ],
        'title' => [
            'label' => 'Başlık',
            'rules' => 'required|min_length[2]|max_length[255]',
        ],
        'content' => [
            'label' => 'Günlük içeriği',
            'rules' => 'required|min_length[2]|max_length[20000]',
        ],
        'mood' => [
            'label' => 'Ruh hâli',
            'rules' => 'required|in_list[great,good,neutral,bad,awful]',
        ],
    ];

    protected $validationMessages = [
        'entry_date' => [
            'required'   => 'Günlük tarihi zorunludur.',
            'valid_date' => 'Geçerli bir günlük tarihi seçin.',
        ],
        'title' => [
            'required'   => 'Başlık zorunludur.',
            'min_length' => 'Başlık en az 2 karakter olmalıdır.',
            'max_length' => 'Başlık en fazla 255 karakter olabilir.',
        ],
        'content' => [
            'required'   => 'Günlük içeriği zorunludur.',
            'min_length' => 'Günlük içeriği en az 2 karakter olmalıdır.',
            'max_length' => 'Günlük içeriği en fazla 20000 karakter olabilir.',
        ],
        'mood' => [
            'in_list' => 'Geçerli bir ruh hâli seçin.',
        ],
    ];

    public function getVisibleTo(int $userId, bool $isAdmin, string $search = '', string $mood = '', string $dateFrom = '', string $dateTo = '', string $scope = 'mine', ?int $perPage = null): array
    {
        $builder = $this->select('journal_entries.*, users.username AS owner_name')
            ->join('users', 'users.id = journal_entries.user_id', 'left')
            ->orderBy('journal_entries.entry_date', 'DESC')
            ->orderBy('journal_entries.created_at', 'DESC');

        if (! $isAdmin || $scope !== 'all') {
            $builder->where('journal_entries.user_id', $userId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('journal_entries.title', $search)
                ->orLike('journal_entries.content', $search)
                ->groupEnd();
        }

        if (in_array($mood, ['great', 'good', 'neutral', 'bad', 'awful'], true)) {
            $builder->where('journal_entries.mood', $mood);
        }

        if ($dateFrom !== '') {
            $builder->where('journal_entries.entry_date >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $builder->where('journal_entries.entry_date <=', $dateTo);
        }

        return $perPage === null ? $builder->findAll() : $builder->paginate($perPage);
    }
}
