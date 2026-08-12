<?php

namespace App\Models;

use CodeIgniter\Model;

class NoteCommentModel extends Model
{
    protected $table            = 'note_comments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['note_id', 'user_id', 'content'];
    protected $useTimestamps    = true;

    protected $validationRules = [
        'note_id' => 'required|is_natural_no_zero',
        'user_id' => 'required|is_natural_no_zero',
        'content' => [
            'label' => 'Yorum',
            'rules' => 'required|min_length[1]|max_length[2000]',
        ],
    ];

    protected $validationMessages = [
        'content' => [
            'required' => 'Yorum boş bırakılamaz.',
            'max_length' => 'Yorum en fazla 2000 karakter olabilir.',
        ],
    ];

    public function getForNote(int $noteId): array
    {
        return $this->select('note_comments.*, users.username')
            ->join('users', 'users.id = note_comments.user_id', 'left')
            ->where('note_comments.note_id', $noteId)
            ->orderBy('note_comments.created_at', 'ASC')
            ->findAll();
    }
}
