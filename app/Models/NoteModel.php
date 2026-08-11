<?php

namespace App\Models;

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
        'is_public',
    ];

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

        'is_public' => [
            'label' => 'Görünürlük',
            'rules' => 'required|in_list[0,1]',
        ],
    ];

    public function getVisibleTo(int $userId, bool $isAdmin): array
    {
        $builder = $this->select('notes.*, users.username AS owner_name')
            ->join('users', 'users.id = notes.user_id', 'left')
            ->orderBy('notes.created_at', 'DESC');

        if (! $isAdmin) {
            $builder->groupStart()
                ->where('notes.user_id', $userId)
                ->orWhere('notes.is_public', 1)
                ->groupEnd();
        }

        return $builder->findAll();
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
}
