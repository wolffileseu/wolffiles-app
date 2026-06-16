<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Tracker\TrackerServerExportService;

class TrackerExportServerCommand extends Command
{
    protected $signature = 'tracker:export-server {server} {--out=} {--limit-stats=0}';

    protected $description = 'Export all tracked data for one server into a multi-tab Excel workbook';

    public function handle(TrackerServerExportService $svc): int
    {
        $id = (int) $this->argument('server');
        $server = DB::table('tracker_servers')->where('id', $id)->first();
        if (! $server) {
            $this->error("Server {$id} not found.");
            return self::FAILURE;
        }

        $name = $server->name ?? ($server->hostname ?? null);
        $label = ($name !== null && $name !== '') ? $name : ('Server ' . $id);
        $this->info("Exporting [{$id}] {$label} ...");

        $out = $this->option('out');
        if (! $out) {
            $out = storage_path('app/exports/' . $svc->filename($id));
        }

        $svc->export($id, $out, (int) $this->option('limit-stats'));

        $this->info('Saved: ' . $out);
        $this->info('Size : ' . number_format(filesize($out) / 1024, 1) . ' KB');
        return self::SUCCESS;
    }
}
