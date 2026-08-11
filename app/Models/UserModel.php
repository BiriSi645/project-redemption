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
        'email',
        'password_hash',
        'role',
    ];

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
