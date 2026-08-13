<?php

namespace App\Libraries;

use App\Models\AuditLogModel;

class AuditLogger
{
    public static function record(?int $userId, string $action, string $description, string $method, string $path, int $statusCode = 200): void
    {
        try {
            (new AuditLogModel())->insert([
                'user_id' => $userId ?: null,
                'action' => mb_substr($action, 0, 80),
                'description' => mb_substr($description, 0, 255),
                'method' => mb_substr(strtoupper($method), 0, 10),
                'path' => mb_substr($path, 0, 255),
                'status_code' => $statusCode,
                'ip_address' => mb_substr(service('request')->getIPAddress(), 0, 45),
                'user_agent' => mb_substr((string) service('request')->getUserAgent(), 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $exception) {
            log_message('error', 'Aktivite logu kaydedilemedi: {message}', ['message' => $exception->getMessage()]);
        }
    }

    public static function describePath(string $path): array
    {
        $rules = [
            '#^admin/users/\d+/role$#' => ['admin.role', 'Kullanıcı rolü değiştirildi'],
            '#^admin/users/\d+/toggle$#' => ['admin.user_status', 'Kullanıcı hesap durumu değiştirildi'],
            '#^notes/\d+/comments/\d+/delete$#' => ['comment.delete', 'Not yorumu silindi'],
            '#^notes/\d+/comments$#' => ['comment.create', 'Nota yorum eklendi'],
            '#^notes/\d+/delete$#' => ['note.delete', 'Not silme işlemi yapıldı'],
            '#^notes/\d+$#' => ['note.update', 'Not güncellendi'],
            '#^notes$#' => ['note.create', 'Not oluşturuldu'],
            '#^tasks/\d+/toggle$#' => ['task.complete', 'Görev durumu değiştirildi'],
            '#^tasks/\d+/delete$#' => ['task.delete', 'Görev silindi'],
            '#^tasks/\d+$#' => ['task.update', 'Görev güncellendi'],
            '#^tasks$#' => ['task.create', 'Görev oluşturuldu'],
            '#^habits/\d+/complete$#' => ['habit.complete', 'Alışkanlık işareti değiştirildi'],
            '#^habits/\d+/toggle$#' => ['habit.status', 'Alışkanlık durumu değiştirildi'],
            '#^habits/\d+/delete$#' => ['habit.delete', 'Alışkanlık silindi'],
            '#^habits/\d+$#' => ['habit.update', 'Alışkanlık güncellendi'],
            '#^habits$#' => ['habit.create', 'Alışkanlık oluşturuldu'],
            '#^journal/\d+/delete$#' => ['journal.delete', 'Günlük kaydı silindi'],
            '#^journal/\d+$#' => ['journal.update', 'Günlük kaydı güncellendi'],
            '#^journal$#' => ['journal.create', 'Günlük kaydı oluşturuldu'],
            '#^games/score$#' => ['game.score', 'Oyun skoru kaydedildi'],
            '#^profile/password$#' => ['profile.password', 'Hesap şifresi değiştirildi'],
            '#^profile/delete$#' => ['profile.delete', 'Hesap silme işlemi yapıldı'],
            '#^profile$#' => ['profile.update', 'Profil ayarları güncellendi'],
        ];

        foreach ($rules as $pattern => $details) {
            if (preg_match($pattern, $path)) {
                return $details;
            }
        }

        return ['system.post', 'Bir form işlemi gerçekleştirildi'];
    }
}
