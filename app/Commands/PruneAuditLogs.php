<?php

namespace App\Commands;

use App\Models\AuditLogModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PruneAuditLogs extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'logs:prune';
    protected $description = 'Belirtilen günden eski aktivite loglarını temizler.';
    protected $usage = 'logs:prune [--days 180] [--dry-run]';
    protected $options = [
        '--days' => 'Kaç günden eski logların temizleneceği (varsayılan: 180).',
        '--dry-run' => 'Kayıtları silmeden kaç kaydın etkileneceğini gösterir.',
    ];

    public function run(array $params)
    {
        $days = (int) (CLI::getOption('days') ?? 180);
        if ($days < 7 || $days > 3650) {
            CLI::error('--days değeri 7 ile 3650 arasında olmalıdır.');
            return EXIT_ERROR;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $model = new AuditLogModel();
        $count = $model->where('created_at <', $cutoff)->countAllResults();

        if (CLI::getOption('dry-run')) {
            CLI::write("{$cutoff} tarihinden eski {$count} log silinecek.", 'yellow');
            return EXIT_SUCCESS;
        }

        if ($count > 0) {
            $model->where('created_at <', $cutoff)->delete();
        }

        cache()->delete('admin_dashboard_summary_v1');
        CLI::write("{$count} eski aktivite logu temizlendi.", 'green');
        return EXIT_SUCCESS;
    }
}
