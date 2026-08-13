<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PruneGameRooms extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'games:prune-rooms';
    protected $description = 'Tamamlanmış ve terk edilmiş çok oyunculu odaları temizler.';
    protected $usage = 'games:prune-rooms [--dry-run]';
    protected $options = ['--dry-run' => 'Kayıtları silmeden sayısını gösterir.'];

    public function run(array $params)
    {
        $db = db_connect();
        $builder = $db->table('game_rooms')->groupStart()
            ->where('status', 'waiting')->where('updated_at <', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->groupEnd()->orGroupStart()
            ->whereIn('status', ['playing', 'completed'])->where('updated_at <', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->groupEnd();
        $count = $builder->countAllResults(false);
        if (! CLI::getOption('dry-run') && $count > 0) $builder->delete();
        CLI::write(CLI::getOption('dry-run') ? "{$count} oda temizlenecek." : "{$count} eski oda temizlendi.", $count ? 'green' : 'yellow');
        return EXIT_SUCCESS;
    }
}
