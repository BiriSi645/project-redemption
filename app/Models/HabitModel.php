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
            ->select('(SELECT COUNT(*) FROM habit_completions hc_total WHERE hc_total.habit_id = habits.id) AS total_completions', false)
            ->select("(SELECT COUNT(*) FROM habit_completions hc_period WHERE hc_period.habit_id = habits.id AND hc_period.period_key = ({$periodKey})) AS completed_count", false)
            ->select("EXISTS(SELECT 1 FROM habit_completions hc_today WHERE hc_today.habit_id = habits.id AND hc_today.completed_on = {$escapedToday}) AS completed_today", false)
            ->where('habits.user_id', $userId)
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
