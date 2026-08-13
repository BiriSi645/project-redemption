<?php

namespace App\Database\Migrations;

use App\Libraries\SudokuPuzzles;
use CodeIgniter\Database\Migration;

class RepairExpertSudokuRooms extends Migration
{
    public function up()
    {
        $rooms = $this->db->table('game_rooms')
            ->select('id, status, version')
            ->where('game', 'sudoku')
            ->where('difficulty', 'expert')
            ->whereIn('status', ['waiting', 'playing'])
            ->get()->getResultArray();
        $puzzle = SudokuPuzzles::get('expert');

        foreach ($rooms as $room) {
            $state = [
                'puzzle' => $puzzle['puzzle'], 'solution' => $puzzle['solution'],
                'values' => str_split($puzzle['puzzle']), 'owners' => array_fill(0, 81, null),
                'mistakes' => 0, 'failed' => false, 'startedAt' => $room['status'] === 'playing' ? time() : null,
                'completed' => false, 'completedAt' => null,
            ];
            $this->db->table('game_rooms')->where('id', $room['id'])->update([
                'state' => json_encode($state, JSON_UNESCAPED_UNICODE),
                'version' => (int) $room['version'] + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        // Geçersiz oyun durumları bilinçli olarak geri yüklenmez.
    }
}
