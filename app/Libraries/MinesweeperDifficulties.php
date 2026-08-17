<?php

namespace App\Libraries;

class MinesweeperDifficulties
{
    public static function all(): array
    {
        return [
            'beginner' => ['label' => 'Başlangıç', 'subtitle' => 'Başlangıç seviyesi', 'rows' => 9, 'cols' => 9, 'mines' => 10],
            'medium' => ['label' => 'Orta', 'subtitle' => 'Orta seviye', 'rows' => 12, 'cols' => 12, 'mines' => 24],
            'expert' => ['label' => 'Zor', 'subtitle' => 'Zor seviye', 'rows' => 16, 'cols' => 16, 'mines' => 40],
            'master' => ['label' => 'Usta', 'subtitle' => 'Usta seviye', 'rows' => 16, 'cols' => 32, 'mines' => 85],
            'nightmare' => ['label' => 'Kabus', 'subtitle' => 'Kabus seviye', 'rows' => 32, 'cols' => 32, 'mines' => 180],
        ];
    }

    public static function has(string $difficulty): bool
    {
        return isset(self::all()[$difficulty]);
    }
}
