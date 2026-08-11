<?php

namespace App\Controllers;

use App\Models\TaskModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Tasks extends BaseController
{
    public function index(): string
    {
        $status = (string) $this->request->getGet('status');

        if (! in_array($status, ['all', 'pending', 'completed'], true)) {
            $status = 'all';
        }

        $userId = (int) session()->get('user_id');

        return view('tasks/index', [
            'title'        => 'Görevler',
            'tasks'        => (new TaskModel())->getForUser($userId, $status),
            'activeStatus' => $status,
        ]);
    }

    public function create(): string
    {
        return view('tasks/create', ['title' => 'Yeni Görev']);
    }

    public function store()
    {
        $taskModel = new TaskModel();
        $data = $this->taskData();
        $data['user_id']      = (int) session()->get('user_id');
        $data['status']       = 'pending';
        $data['completed_at'] = null;

        if (! $taskModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $taskModel->errors());
        }

        return redirect()->to(site_url('tasks'))->with('success', 'Görev oluşturuldu.');
    }

    public function edit(int $id): string
    {
        return view('tasks/edit', [
            'title' => 'Görevi Düzenle',
            'task'  => $this->findOwnedTask($id),
        ]);
    }

    public function update(int $id)
    {
        $this->findOwnedTask($id);
        $taskModel = new TaskModel();

        if (! $taskModel->update($id, $this->taskData())) {
            return redirect()->back()->withInput()->with('errors', $taskModel->errors());
        }

        return redirect()->to(site_url('tasks'))->with('success', 'Görev güncellendi.');
    }

    public function toggle(int $id)
    {
        $task = $this->findOwnedTask($id);
        $completed = $task['status'] !== 'completed';

        (new TaskModel())->update($id, [
            'status'       => $completed ? 'completed' : 'pending',
            'completed_at' => $completed ? date('Y-m-d H:i:s') : null,
        ]);

        return redirect()->back()->with(
            'success',
            $completed ? 'Görev tamamlandı.' : 'Görev yeniden açıldı.'
        );
    }

    public function delete(int $id)
    {
        $this->findOwnedTask($id);
        (new TaskModel())->delete($id);

        return redirect()->to(site_url('tasks'))->with('success', 'Görev silindi.');
    }

    private function taskData(): array
    {
        $dueDate = trim((string) $this->request->getPost('due_date'));
        $dueTime = trim((string) $this->request->getPost('due_time'));

        return [
            'title'       => trim((string) $this->request->getPost('title')),
            'description' => trim((string) $this->request->getPost('description')),
            'priority'    => (string) $this->request->getPost('priority'),
            'due_date'    => $dueDate === '' ? null : $dueDate,
            'due_time'    => $dueDate === '' ? null : ($dueTime === '' ? '23:59' : $dueTime),
        ];
    }

    private function findOwnedTask(int $id): array
    {
        $task = (new TaskModel())->find($id);

        if (! $task || (int) $task['user_id'] !== (int) session()->get('user_id')) {
            throw PageNotFoundException::forPageNotFound('Görev bulunamadı.');
        }

        return $task;
    }
}
