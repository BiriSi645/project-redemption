<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'username',
        'bio',
        'email',
        'password_hash',
        'role',
        'theme',
        'language',
        'notifications_enabled',
        'is_active',
        'last_seen_at',
    ];

    public function activeUsers(int $limit = 20): array
    {
        return $this->select('id, username, role, last_seen_at')
            ->where('is_active', 1)
            ->where('last_seen_at >=', date('Y-m-d H:i:s', strtotime('-90 seconds')))
            ->orderBy('last_seen_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'username' => [
            'label' => 'Kullanıcı adı',
            'rules' => 'required|min_length[3]|max_length[100]|is_unique[users.username]',
        ],

        'email' => [
            'label' => 'E-posta',
            'rules' => 'required|valid_email|is_unique[users.email]',
        ],

        'password_hash' => [
            'label' => 'Şifre',
            'rules' => 'required',
        ],
    ];

    protected $validationMessages = [
        'username' => [
            'required'   => 'Kullanıcı adı zorunludur.',
            'min_length' => 'Kullanıcı adı en az 3 karakter olmalıdır.',
            'is_unique'  => 'Bu kullanıcı adı zaten kullanılıyor.',
        ],

        'email' => [
            'required'    => 'E-posta zorunludur.',
            'valid_email' => 'Geçerli bir e-posta adresi gir.',
            'is_unique'   => 'Bu e-posta zaten kullanılıyor.',
        ],

        'password_hash' => [
            'required' => 'Şifre zorunludur.',
        ],
    ];
}
