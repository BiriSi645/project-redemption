<?php

namespace App\Models;

use CodeIgniter\Model;

class HabitModel extends Model
{
    protected $table            = 'habits';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['user_id', 'title', 'description', 'frequency', 'target_count', 'is_active'];
    protected $useTimestamps    = true;

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'title' => [
            'label' => 'Başlık',
            'rules' => 'required|min_length[2]|max_length[255]',
        ],
        'description' => [
            'label' => 'Açıklama',
            'rules' => 'permit_empty|max_length[2000]',
        ],
        'frequency' => [
            'label' => 'Sıklık',
            'rules' => 'required|in_list[daily,weekly,monthly]',
        ],
        'target_count' => [
            'label' => 'Dönem hedefi',
            'rules' => 'required|is_natural_no_zero|less_than_equal_to[31]',
        ],
    ];

    public function getForUserWithCurrentStatus(int $userId, bool $activeOnly = false, ?int $limit = null): array
    {
        $today = date('Y-m-d');
        $dailyKey = $this->db->escape(self::periodKey('daily'));
        $weeklyKey = $this->db->escape(self::periodKey('weekly'));
        $monthlyKey = $this->db->escape(self::periodKey('monthly'));
        $escapedToday = $this->db->escape($today);
        $periodKey = "CASE habits.frequency WHEN 'weekly' THEN {$weeklyKey} WHEN 'monthly' THEN {$monthlyKey} ELSE {$dailyKey} END";

        $builder = $this->select('habits.*')
            ->select('COUNT(habit_completions.id) AS total_completions', false)
            ->select("SUM(CASE WHEN habit_completions.period_key = ({$periodKey}) THEN 1 ELSE 0 END) AS completed_count", false)
            ->select("MAX(CASE WHEN habit_completions.completed_on = {$escapedToday} THEN 1 ELSE 0 END) AS completed_today", false)
            ->join('habit_completions', 'habit_completions.habit_id = habits.id AND habit_completions.user_id = habits.user_id', 'left')
            ->where('habits.user_id', $userId)
            ->groupBy('habits.id, habits.user_id, habits.title, habits.description, habits.frequency, habits.target_count, habits.is_active, habits.created_at, habits.updated_at', false)
            ->orderBy('habits.is_active', 'DESC')
            ->orderBy('habits.created_at', 'DESC');

        if ($activeOnly) {
            $builder->where('habits.is_active', 1);
        }
        if ($limit !== null) {
            $builder->limit($limit);
        }

        $habits = $builder->findAll();

        foreach ($habits as &$habit) {
            $habit['period_key'] = self::periodKey($habit['frequency']);
            $habit['period_label'] = self::periodLabel($habit['frequency']);
            $habit['goal_label'] = self::goalLabel($habit['frequency'], (int) $habit['target_count']);
            $habit['completed_count'] = (int) $habit['completed_count'];
            $habit['completed_today'] = (int) $habit['completed_today'] === 1;
            $habit['total_completions'] = (int) $habit['total_completions'];
            $habit['completed'] = $habit['completed_count'] >= (int) $habit['target_count'];
            $habit['progress_percent'] = min(100, (int) round(($habit['completed_count'] / max(1, (int) $habit['target_count'])) * 100));
        }
        unset($habit);

        return $habits;
    }

    public static function periodKey(string $frequency, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return match ($frequency) {
            'weekly' => date('o-\WW', $timestamp),
            'monthly' => date('Y-m', $timestamp),
            default => date('Y-m-d', $timestamp),
        };
    }

    public static function periodLabel(string $frequency): string
    {
        return match ($frequency) {
            'weekly' => 'Bu hafta',
            'monthly' => 'Bu ay',
            default => 'Bugün',
        };
    }

    public static function goalLabel(string $frequency, int $target): string
    {
        return match ($frequency) {
            'weekly' => "Haftada {$target} gün",
            'monthly' => "Ayda {$target} gün",
            default => 'Her gün',
        };
    }
}
