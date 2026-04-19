<?php

namespace App\Services\Banner;

use App\Models\Tracker\TrackerServer;
use Illuminate\Support\Facades\DB;

class ServerBannerRenderer extends BannerRenderer
{
    public function __construct(protected TrackerServer $server)
    {
        parent::__construct(560, 95);
    }

    public function render(): string
    {
        $this->drawHeader();
        $this->drawLabels();
        $this->drawValues();
        $this->drawGraph();
        return $this->output();
    }

    protected function drawHeader(): void
    {
        $title = 'WOLFFILES.EU';
        $w = $this->textWidth($title, 7, self::FONT_BOLD);
        $this->text($title, (int) (($this->width - $w) / 2), 11, 7, self::THEME['accent'], self::FONT_BOLD);
        imageline($this->canvas, 1, 14, $this->width - 2, 14, $this->color(self::THEME['border']));
    }

    protected function drawLabels(): void
    {
        $c = self::THEME['accent'];
        $f = self::FONT_CONDENSED_BOLD;
        // Row 1 — Servername spans the full width (no right-side label)
        $this->text('SERVERNAME:',  4,   34, 7, $c, $f);
        // Row 2 — IP | STATUS
        $this->text('IP ADDRESS:',  4,   57, 7, $c, $f);
        $this->text('STATUS:',      286, 57, 7, $c, $f);
        // Row 3 — PLAYERS | CURRENT MAP
        $this->text('PLAYERS:',     4,   80, 7, $c, $f);
        $this->text('CURRENT MAP:', 286, 80, 7, $c, $f);
        // Graph caption (above graph)
        $this->text('Last 24h # of players', 420, 22, 6, self::THEME['value_muted'], $f);
    }

    protected function drawValues(): void
    {
        $s = $this->server;
        $white = self::THEME['value'];

        // Row 1: Servername — now up to x=435 (much more room than before)
        $nameX = 80;
        if ($s->country_code) {
            $flag = public_path('images/flags/' . strtolower($s->country_code) . '.png');
            if ($this->drawImage($flag, 80, 25, 16, 11)) {
                $nameX = 100;
            }
        }
        $this->coloredText(
            $s->hostname ?: 'Unknown',
            $nameX, 34, 8, self::FONT_BOLD,
            maxWidth: 435 - $nameX
        );

        // Row 2: IP:Port — limited to before STATUS label at x=286
        $this->text(($s->ip ?: '?') . ':' . ($s->port ?: '?'), 80, 57, 8, $white, self::FONT_BOLD);

        // Row 2: STATUS — graph area starts at x=440, so status value from 340 to ~430
        $online = (bool) ($s->is_online ?? false);
        $this->text(
            $online ? 'Online' : 'Offline',
            340, 57, 8,
            $online ? self::THEME['online'] : self::THEME['offline'],
            self::FONT_BOLD
        );

        // Row 3: PLAYERS
        $cur = (int) ($s->current_players ?? 0);
        $max = (int) ($s->max_players ?? 0);
        $this->text("{$cur}/{$max}", 80, 80, 8, $white, self::FONT_BOLD);

        // Row 3: CURRENT MAP — no graph on this row, so full width to right edge
        if (!empty($s->current_map)) {
            $this->coloredText(
                $s->current_map,
                370, 80, 8, self::FONT_BOLD,
                maxWidth: $this->width - 380
            );
        }
    }

    protected function drawGraph(): void
    {
        $data = DB::table('tracker_server_history')
            ->where('server_id', $this->server->id)
            ->where('polled_at', '>=', now()->subHours(24))
            ->orderBy('polled_at')
            ->pluck('players')
            ->toArray();

        if (empty($data)) {
            return;
        }
        if (count($data) > 80) {
            $chunks = array_chunk($data, (int) ceil(count($data) / 80));
            $data = array_map(fn ($c) => max($c), $chunks);
        }

        $maxP = (int) max(($this->server->max_players ?: 1), max($data));

        // Bigger graph — spans over rows 2 + 3 visually (y=28 to y=70)
        $gx = 435; $gy = 28; $gw = 120; $gh = 36;

        $this->sparkline($data, $gx, $gy, $gw, $gh, $maxP);

        $muted = self::THEME['value_muted'];
        $lblMax = (string) $maxP;
        $lblMaxW = $this->textWidth($lblMax, 6, self::FONT_CONDENSED_BOLD);
        $this->text($lblMax, $gx + $gw - $lblMaxW - 2, $gy + 6,  6, $muted, self::FONT_CONDENSED_BOLD);
        $this->text('0',     $gx + $gw - 6,            $gy + $gh, 6, $muted, self::FONT_CONDENSED_BOLD);
    }
}
