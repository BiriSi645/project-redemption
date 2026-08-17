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
        'profile_url',
        'experience_points',
        'email',
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at',
        'email_verification_attempts',
        'email_verification_sent_at',
        'password_hash',
        'password_reset_token',
        'password_reset_expires_at',
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
            'rules' => 'required|min_length[3]|max_length[50]',
        ],

        'email' => [
            'label' => 'E-posta',
            'rules' => 'required|valid_email|max_length[255]',
        ],

        'email_verification_attempts' => [
            'label' => 'Doğrulama deneme sayısı',
            'rules' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[5]',
        ],

        'email_verification_expires_at' => [
            'label' => 'Doğrulama kodu son kullanma zamanı',
            'rules' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ],

        'email_verification_sent_at' => [
            'label' => 'Doğrulama kodu gönderim zamanı',
            'rules' => 'permit_empty|valid_date[Y-m-d H:i:s]',
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
