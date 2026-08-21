<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\AnnouncementModel;
use App\Models\HabitModel;
use App\Models\JournalEntryModel;
use App\Models\NoteCommentModel;
use App\Models\NoteModel;
use App\Models\TaskModel;
use App\Models\UserModel;

class Admin extends BaseController
{
    public function index(): string
    {
        $dashboardData = cache()->remember('admin_dashboard_summary_v1', 60, static function (): array {
            return [
                'counts' => [
                    'users' => (new UserModel())->countAllResults(),
                    'activeUsers' => (new UserModel())->where('is_active', 1)->countAllResults(),
                    'notes' => (new NoteModel())->countAllResults(),
                    'comments' => (new NoteCommentModel())->countAllResults(),
                    'tasks' => (new TaskModel())->countAllResults(),
                    'journals' => (new JournalEntryModel())->countAllResults(),
                    'habits' => (new HabitModel())->countAllResults(),
                ],
                'todayActivity' => (new AuditLogModel())->where('created_at >=', date('Y-m-d 00:00:00'))->countAllResults(),
                'failedLogins' => (new AuditLogModel())->where('action', 'auth.login_failed')->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-7 days')))->countAllResults(),
                'recentLogs' => (new AuditLogModel())->recent(8),
                'recentUsers' => (new UserModel())->orderBy('created_at', 'DESC')->limit(5)->findAll(),
                'activityByDay' => (new AuditLogModel())
                    ->select('DATE(created_at) AS activity_date, COUNT(*) AS total')
                    ->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-6 days')))
                    ->groupBy('DATE(created_at)')
                    ->orderBy('activity_date', 'ASC')
                    ->findAll(),
            ];
        });

        return view('admin/index', array_merge([
            'title' => 'Admin Dashboard',
        ], $dashboardData));
    }

    public function users(): string
    {
        $managementSections = [
            'users' => 'Kullanıcı Yönetimi',
            'notifications' => 'Bildirim Yönetimi',
        ];
        $section = (string) $this->request->getGet('section');
        if (! array_key_exists($section, $managementSections)) {
            $section = 'users';
        }
        $search = trim((string) $this->request->getGet('q'));
        $role = (string) $this->request->getGet('role');
        $status = (string) $this->request->getGet('status');
        $userModel = new UserModel();

        if ($search !== '') {
            $userModel->groupStart()->like('username', $search)->orLike('email', $search)->groupEnd();
        }
        if (in_array($role, ['user', 'admin'], true)) {
            $userModel->where('role', $role);
        }
        if (in_array($status, ['active', 'inactive'], true)) {
            $userModel->where('is_active', $status === 'active' ? 1 : 0);
        }

        $users = [];
        $pager = null;
        $announcements = [];
        $announcementPager = null;
        if ($section === 'users') {
            $users = $userModel->orderBy('created_at', 'DESC')->paginate(15, 'users');
            $pager = $userModel->pager;
        } else {
            $announcementModel = new AnnouncementModel();
            $announcements = $announcementModel->withAuthor()->orderBy('announcements.created_at', 'DESC')->paginate(6, 'announcements');
            $announcementPager = $announcementModel->pager;
        }

        return view('admin/users', [
            'title' => 'Yönetim Merkezi',
            'users' => $users,
            'pager' => $pager,
            'announcements' => $announcements,
            'announcementPager' => $announcementPager,
            'totalUserCount' => (new UserModel())->countAllResults(),
            'activeSection' => $section,
            'managementSections' => $managementSections,
            'search' => $search,
            'activeRole' => $role,
            'activeStatus' => $status,
        ]);
    }

    public function logs(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $action = trim((string) $this->request->getGet('action'));
        $date = (string) $this->request->getGet('date');
        $status = (string) $this->request->getGet('status');
        $logModel = new AuditLogModel();
        $logModel->select('audit_logs.*, users.username')->join('users', 'users.id = audit_logs.user_id', 'left');

        if ($search !== '') {
            $logModel->groupStart()->like('users.username', $search)->orLike('audit_logs.description', $search)->orLike('audit_logs.ip_address', $search)->groupEnd();
        }
        if ($action !== '') {
            $logModel->where('audit_logs.action', $action);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $logModel->where('audit_logs.created_at >=', $date . ' 00:00:00')->where('audit_logs.created_at <=', $date . ' 23:59:59');
        } else {
            $date = '';
        }
        if ($status === 'success') {
            $logModel->where('audit_logs.status_code <', 400);
        } elseif ($status === 'failed') {
            $logModel->where('audit_logs.status_code >=', 400);
        }

        return view('admin/logs', [
            'title' => 'Aktivite Logları',
            'logs' => $logModel->orderBy('audit_logs.created_at', 'DESC')->paginate(30),
            'pager' => $logModel->pager,
            'actions' => array_column((new AuditLogModel())->select('action')->distinct()->orderBy('action', 'ASC')->findAll(), 'action'),
            'search' => $search,
            'activeAction' => $action,
            'activeDate' => $date,
            'activeStatus' => $status,
        ]);
    }

    public function role(int $id)
    {
        $role = (string) $this->request->getPost('role');
        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (! $user || ! in_array($role, ['user', 'admin'], true)) {
            return redirect()->to(site_url('admin/users'));
        }
        if ($id === (int) session()->get('user_id') && $user['role'] === 'admin' && $role === 'user' && $userModel->where('role', 'admin')->where('is_active', 1)->countAllResults() <= 1) {
            return redirect()->to(site_url('admin/users'))->with('errors', ['admin' => 'Son aktif adminin rolü değiştirilemez.']);
        }

        $userModel->skipValidation(true)->update($id, ['role' => $role]);
        cache()->delete('auth_user_' . $id);
        cache()->delete('admin_dashboard_summary_v1');

        return redirect()->to(site_url('admin/users'))->with('success', 'Kullanıcı rolü güncellendi.');
    }

    public function toggle(int $id)
    {
        $userModel = new UserModel();
        $user = $userModel->find($id);
        if (! $user) {
            return redirect()->to(site_url('admin/users'));
        }
        if ($id === (int) session()->get('user_id')) {
            return redirect()->to(site_url('admin/users'))->with('errors', ['admin' => 'Kendi hesabınızı devre dışı bırakamazsınız.']);
        }

        $userModel->skipValidation(true)->update($id, ['is_active' => (int) $user['is_active'] === 1 ? 0 : 1]);
        cache()->delete('auth_user_' . $id);
        cache()->delete('admin_dashboard_summary_v1');

        return redirect()->to(site_url('admin/users'))->with('success', 'Hesap durumu güncellendi.');
    }
    public function destroy(int $id)
    {
        $userModel = new UserModel();

        $user = $userModel->find($id);

        if (! $user) {
            return redirect()
                ->to(site_url('admin/users'))
                ->with('errors', [
                    'user' => 'Kullanıcı bulunamadı.',
                ]);
        }

        /*
        * Admin kendi hesabını silemesin.
        */
        if ($id === (int) session()->get('user_id')) {
            return redirect()
                ->to(site_url('admin/users'))
                ->with('errors', [
                    'admin' => 'Kendi hesabınızı silemezsiniz.',
                ]);
        }

        /*
        * Son adminin silinmesini de engelle.
        */
        if ($user['role'] === 'admin') {
            $activeAdminCount = $userModel
                ->where('role', 'admin')
                ->where('is_active', 1)
                ->countAllResults();

            if (
                (int) $user['is_active'] === 1
                && $activeAdminCount <= 1
            ) {
                return redirect()
                    ->to(site_url('admin/users'))
                    ->with('errors', [
                        'admin' => 'Son aktif admin hesabı silinemez.',
                    ]);
            }
        }

        $db = db_connect();

        $sharedOwnedProjects = $db
            ->table('projects')
            ->select('projects.id, projects.name')
            ->join('project_members', 'project_members.project_id = projects.id')
            ->where('projects.owner_id', $id)
            ->where('project_members.user_id !=', $id)
            ->where('project_members.status', 'accepted')
            ->groupBy(['projects.id', 'projects.name'])
            ->get()
            ->getResultArray();

        if ($sharedOwnedProjects !== []) {
            return redirect()
                ->to(site_url('admin/users'))
                ->with('errors', [
                    'user' => 'Bu kullanıcı, başka üyeleri bulunan projelerin sahibi olduğu için silinemez. Ortak proje verilerini korumak için önce proje sahipliği devredilmelidir.',
                ]);
        }

        /*
        * Her şey ya silinsin ya hiçbir şey silinmesin.
        */
        $db->transStart();

        /*
        * Bunlar ON DELETE SET NULL olduğu için
        * kullanıcı silinmeden önce tamamen siliyoruz.
        */

        $db->table('audit_logs')
            ->where('user_id', $id)
            ->delete();

        $db->table('notifications')
            ->groupStart()
                ->where('user_id', $id)
                ->orWhere('actor_user_id', $id)
            ->groupEnd()
            ->delete();

        /*
        * Asıl kullanıcıyı sil.
        *
        * Foreign key CASCADE sayesinde buna bağlı:
        *
        * notes
        * tasks
        * journal_entries
        * habits
        * habit_completions
        * note_comments
        * note_mentions
        * game_scores
        * game_rooms
        * direct_conversations
        * direct_messages
        * user_blocks
        * vb.
        *
        * otomatik silinecek.
        */
        $userModel
            ->skipValidation(true)
            ->delete($id);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()
                ->to(site_url('admin/users'))
                ->with('errors', [
                    'user' => 'Kullanıcı silinirken bir hata oluştu.',
                ]);
        }

        /*
        * Kullanıcıya ait cache kayıtlarını temizle.
        */
        cache()->delete('auth_user_' . $id);
        cache()->delete('admin_dashboard_summary_v1');

        return redirect()
            ->to(site_url('admin/users'))
            ->with(
                'success',
                $user['username'] . ' kullanıcısı ve tüm verileri kalıcı olarak silindi.'
            );
    }
}
