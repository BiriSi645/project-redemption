<?php

namespace App\Controllers;

use App\Models\HabitCompletionModel;
use App\Models\HabitModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Libraries\ExperienceService;

class Habits extends BaseController
{
    public function index(): string
    {
        $habits = (new HabitModel())->getForUserWithCurrentStatus((int) session()->get('user_id'));
        $activeHabits = array_values(array_filter($habits, static fn (array $habit): bool => (int) $habit['is_active'] === 1));
        $completedCount = array_sum(array_column($activeHabits, 'completed_count'));
        $targetCount = array_sum(array_map(static fn (array $habit): int => (int) $habit['target_count'], $activeHabits));

        return view('habits/index', [
            'title' => 'Alışkanlıklar',
            'habits' => $habits,
            'progressSummary' => [
                'completed' => $completedCount,
                'target' => $targetCount,
                'percent' => $targetCount === 0 ? 0 : min(100, (int) round(($completedCount / $targetCount) * 100)),
                'active' => count($activeHabits),
                'achieved' => count(array_filter($activeHabits, static fn (array $habit): bool => $habit['completed'])),
            ],
        ]);
    }

    public function create(): string
    {
        return view('habits/create', ['title' => 'Yeni Alışkanlık']);
    }

    public function store()
    {
        $habitModel = new HabitModel();
        $data = $this->habitData();
        if ($targetError = $this->targetError($data)) {
            return redirect()->back()->withInput()->with('errors', ['target_count' => $targetError]);
        }
        $data['user_id'] = (int) session()->get('user_id');
        $data['is_active'] = 1;

        if (! $habitModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $habitModel->errors());
        }

        return redirect()->to(site_url('habits'))->with('success', 'Alışkanlık eklendi. İlk dönem için işaretlemeye başlayabilirsiniz.');
    }

    public function edit(int $id): string
    {
        return view('habits/edit', [
            'title' => 'Alışkanlığı Düzenle',
            'habit' => $this->findOwnedHabit($id),
        ]);
    }

    public function update(int $id)
    {
        $this->findOwnedHabit($id);
        $habitModel = new HabitModel();
        $data = $this->habitData();
        if ($targetError = $this->targetError($data)) {
            return redirect()->back()->withInput()->with('errors', ['target_count' => $targetError]);
        }

        if (! $habitModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $habitModel->errors());
        }

        return redirect()->to(site_url('habits'))->with('success', 'Alışkanlık güncellendi.');
    }

    public function complete(int $id)
    {
        $habit = $this->findOwnedHabit($id);

        if ((int) $habit['is_active'] !== 1) {
            return redirect()->to(site_url('habits'))->with('errors', ['habit' => 'Pasif bir alışkanlık işaretlenemez.']);
        }

        $completionModel = new HabitCompletionModel();
        $periodKey = HabitModel::periodKey($habit['frequency']);
        $completion = $completionModel
            ->where('habit_id', $id)
            ->where('completed_on', date('Y-m-d'))
            ->first();

        if ($completion) {
            $completionModel->delete($completion['id']);
            $message = 'Bu döneme ait işaret kaldırıldı.';
        } else {
            $completedCount = $completionModel
                ->where('habit_id', $id)
                ->where('period_key', $periodKey)
                ->countAllResults();
            if ($completedCount >= (int) $habit['target_count']) {
                return redirect()->back()->with('success', 'Bu dönemin hedefi zaten tamamlandı.');
            }
            $completionModel->insert([
                'habit_id' => $id,
                'user_id' => (int) session()->get('user_id'),
                'period_key' => $periodKey,
                'completed_on' => date('Y-m-d'),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            (new ExperienceService())->award((int) session()->get('user_id'), 'habit_completed', 'habit:' . $id . ':' . date('Y-m-d'));
            $message = 'Alışkanlık bu dönem için tamamlandı.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function toggle(int $id)
    {
        $habit = $this->findOwnedHabit($id);
        $active = (int) $habit['is_active'] !== 1;
        (new HabitModel())->skipValidation(true)->update($id, ['is_active' => $active ? 1 : 0]);

        return redirect()->to(site_url('habits'))->with('success', $active ? 'Alışkanlık yeniden etkinleştirildi.' : 'Alışkanlık duraklatıldı.');
    }

    public function delete(int $id)
    {
        $this->findOwnedHabit($id);
        (new HabitModel())->delete($id);

        return redirect()->to(site_url('habits'))->with('success', 'Alışkanlık ve geçmiş işaretlemeleri silindi.');
    }

    private function habitData(): array
    {
        return [
            'title' => trim((string) $this->request->getPost('title')),
            'description' => trim((string) $this->request->getPost('description')),
            'frequency' => (string) $this->request->getPost('frequency'),
            'target_count' => (int) $this->request->getPost('target_count'),
        ];
    }

    private function targetError(array $data): ?string
    {
        $target = (int) $data['target_count'];

        if ($target < 1) {
            return 'Hedef en az 1 gün olmalıdır.';
        }
        if ($data['frequency'] === 'daily' && $target !== 1) {
            return 'Günlük alışkanlıkların hedefi 1 gün olmalıdır.';
        }
        if ($data['frequency'] === 'weekly' && $target > 7) {
            return 'Haftalık hedef en fazla 7 gün olabilir.';
        }
        if ($data['frequency'] === 'monthly' && $target > 31) {
            return 'Aylık hedef en fazla 31 gün olabilir.';
        }

        return null;
    }

    private function findOwnedHabit(int $id): array
    {
        $habit = (new HabitModel())->find($id);

        if (! $habit || (int) $habit['user_id'] !== (int) session()->get('user_id')) {
            throw PageNotFoundException::forPageNotFound('Alışkanlık bulunamadı.');
        }

        return $habit;
    }
}
