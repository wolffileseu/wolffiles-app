<?php

namespace App\Services\Banner;

use App\Models\Tracker\TrackerPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlayerBannerRenderer extends BannerRenderer
{
    /** @var int 1-4, which info is shown on Row 3 right side */
    protected int $variant;

    public function __construct(protected TrackerPlayer $player, int $variant = 1)
    {
        $this->variant = max(1, min(4, $variant));
        parent::__construct(560, 95);
    }

    public function render(): string
    {
        $this->drawHeader();
        $this->drawLabels();
        $this->drawValues();
        $this->drawGraphArea();
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

        // Row 1
        $this->text('PLAYER:',   4,   34, 7, $c, $f);
        // Row 2
        $this->text('RANK:',     4,   57, 7, $c, $f);
        $this->text('ELO:',      180, 57, 7, $c, $f);
        // Row 3 left (always)
        $this->text('PLAYTIME:', 4,   80, 7, $c, $f);

        // Row 3 right — depends on variant
        switch ($this->variant) {
            case 2:
                $this->text('FAV SRV:', 180, 80, 7, $c, $f);
                break;
            case 3:
                $this->text('NOW ON:',  180, 80, 7, $c, $f);
                break;
            case 4:
                $this->text('FAV:',     180, 74, 6, $c, $f);
                $this->text('NOW:',     180, 87, 6, $c, $f);
                break;
            // case 1: no right-side label
        }
    }

    protected function drawValues(): void
    {
        $p = $this->player;
        $white = self::THEME['value'];
        $muted = self::THEME['value_muted'];

        // ===== Row 1: Flag + Name + Status =====
        $nameX = 80;
        if ($p->country_code) {
            $flag = public_path('images/flags/' . strtolower($p->country_code) . '.png');
            if ($this->drawImage($flag, 80, 25, 16, 11)) {
                $nameX = 100;
            }
        }

        $online = $this->isOnline();
        $statusLabel = $online ? 'Online' : 'Offline';
        $statusColor = $online ? self::THEME['online'] : self::THEME['offline'];

        $statusW  = $this->textWidth($statusLabel, 8, self::FONT_BOLD);
        $textX    = $this->width - $statusW - 8;          // 8px right margin
        $dotCX    = $textX - 8;                           // gap between dot and text
        $dotCY    = 31;                                   // visual mid of size-8 text
        $dotSize  = 6;

        imagefilledellipse(
            $this->canvas,
            $dotCX, $dotCY,
            $dotSize, $dotSize,
            $this->color($statusColor)
        );
        $this->text($statusLabel, $textX, 34, 8, $statusColor, self::FONT_BOLD);

        // Name — constrained to leave room for status indicator on the right
        $nameMaxEnd   = $dotCX - intdiv($dotSize, 2) - 6;
        $nameMaxWidth = max(60, $nameMaxEnd - $nameX);
        $this->coloredText(
            $p->name ?: 'Unknown',
            $nameX, 34, 8, self::FONT_BOLD,
            maxWidth: $nameMaxWidth
        );

        // ===== Row 2: Rank + ELO =====
        $rank = $this->computeRank();
        $rankStr = $rank ? "#{$rank['rank']} / {$rank['total']}" : '-';
        $this->text($rankStr, 80, 57, 8, $white, self::FONT_BOLD);

        $elo  = (int) round((float) ($p->elo_rating ?? 0));
        $peak = (int) round((float) ($p->elo_peak ?? 0));
        $eloStr = $elo > 0 ? (string) $elo : '-';
        $this->text($eloStr, 220, 57, 8, $white, self::FONT_BOLD);
        if ($peak > 0 && $peak > $elo) {
            $eloW = $this->textWidth($eloStr, 8);
            $this->text("peak {$peak}", 220 + $eloW + 6, 59, 6, $muted, self::FONT_CONDENSED_BOLD);
        }

        // ===== Row 3: Playtime (always) + variant-specific right side =====
        $mins  = (int) ($p->total_play_time_minutes ?? 0);
        $hours = (int) round($mins / 60);
        $this->text("{$hours}h", 80, 80, 8, $white, self::FONT_BOLD);

        switch ($this->variant) {
            case 1:
                // No right-side content
                break;
            case 2:
                $this->drawRightValue($this->favoriteServer(), 235, 80, 8, 190);
                break;
            case 3:
                $this->drawRightValue($this->currentServer(), 235, 80, 8, 190);
                break;
            case 4:
                // Stacked: FAV on top, NOW below (smaller font, tighter)
                $this->drawRightValue($this->favoriteServer(), 215, 74, 7, 210);
                $this->drawRightValue($this->currentServer(),  215, 87, 7, 210);
                break;
        }
    }

    /**
     * Draw a hostname value with dash fallback. Uses coloredText so
     * ET color codes in server names render properly.
     */
    protected function drawRightValue(?string $value, int $x, int $y, int $size, int $maxWidth): void
    {
        if ($value !== null && $value !== '') {
            $this->coloredText($value, $x, $y, $size, self::FONT_BOLD, maxWidth: $maxWidth);
        } else {
            $this->text('-', $x, $y, $size, self::THEME['value_muted'], self::FONT_BOLD);
        }
    }

    /**
     * Right-side graph area (x=435, w=120). Draws ELO sparkline if
     * tracker_elo_history has 24h data, otherwise XP + Sessions fallback.
     */
    protected function drawGraphArea(): void
    {
        $data = [];
        if (Schema::hasTable('tracker_elo_history')) {
            $data = DB::table('tracker_elo_history')
                ->where('player_id', $this->player->id)
                ->where('recorded_at', '>=', now()->subHours(24))
                ->orderBy('recorded_at')
                ->pluck('elo_after')
                ->toArray();
        }

        $gx = 435; $gy = 28; $gw = 120; $gh = 36;
        $muted  = self::THEME['value_muted'];
        $white  = self::THEME['value'];
        $accent = self::THEME['accent'];
        $f      = self::FONT_CONDENSED_BOLD;

        if (empty($data)) {
            // Fallback: XP + Sessions, aligned with rows 2 + 3
            $p = $this->player;
            $xp       = (int) ($p->total_xp ?? 0);
            $sessions = (int) ($p->total_sessions ?? 0);

            $this->text('XP:',          $gx,      57, 7, $accent, $f);
            $this->text($this->formatNumber($xp), $gx + 26, 57, 8, $white, self::FONT_BOLD);

            $this->text('SESSIONS:',    $gx,      80, 7, $accent, $f);
            $this->text((string) $sessions, $gx + 60, 80, 8, $white, self::FONT_BOLD);
            return;
        }

        // Sparkline mode
        if (count($data) > 80) {
            $chunks = array_chunk($data, (int) ceil(count($data) / 80));
            $data = array_map(fn ($c) => (float) max($c), $chunks);
        }

        $min   = (float) min($data);
        $max   = (float) max($data);
        $range = max($max - $min, 1.0);
        $normalized = array_map(fn ($v) => $v - $min, $data);

        $this->text('Last 24h ELO', $gx, 22, 6, $muted, $f);
        $this->sparkline($normalized, $gx, $gy, $gw, $gh, $range);

        $lblMax  = (string) (int) round($max);
        $lblMaxW = $this->textWidth($lblMax, 6, $f);
        $this->text($lblMax, $gx + $gw - $lblMaxW - 2, $gy + 6, 6, $muted, $f);
        $lblMin  = (string) (int) round($min);
        $lblMinW = $this->textWidth($lblMin, 6, $f);
        $this->text($lblMin, $gx + $gw - $lblMinW - 2, $gy + $gh, 6, $muted, $f);
    }

    protected function isOnline(): bool
    {
        return DB::table('tracker_player_sessions')
            ->where('player_id', $this->player->id)
            ->whereNull('ended_at')
            ->exists();
    }

    /**
     * Returns the player's best 30-day playtime rank across game families (et/rtcw).
     * Same source as /tracker/rankings/players. Null if no 30d activity.
     *
     * @return array{rank:int,total:int}|null
     */
    protected function computeRank(): ?array
    {
        $row = DB::table('tracker_player_rankings_30d')
            ->where('player_id', $this->player->id)
            ->orderBy('rank')
            ->first(['rank', 'total_in_game']);

        if ($row === null) {
            return null;
        }

        return ['rank' => (int) $row->rank, 'total' => (int) $row->total_in_game];
    }

    protected function favoriteServer(): ?string
    {
        $row = DB::table('tracker_player_sessions as s')
            ->leftJoin('tracker_servers as srv', 'srv.id', '=', 's.server_id')
            ->selectRaw('srv.hostname as hostname, SUM(s.duration_minutes) as total_min')
            ->where('s.player_id', $this->player->id)
            ->whereNotNull('srv.hostname')
            ->groupBy('s.server_id', 'srv.hostname')
            ->orderByDesc('total_min')
            ->first();

        return $row?->hostname;
    }

    /**
     * The server the player is currently connected to (ended_at IS NULL).
     * Returns null if the player is offline.
     */
    protected function currentServer(): ?string
    {
        $row = DB::table('tracker_player_sessions as s')
            ->leftJoin('tracker_servers as srv', 'srv.id', '=', 's.server_id')
            ->where('s.player_id', $this->player->id)
            ->whereNull('s.ended_at')
            ->whereNotNull('srv.hostname')
            ->orderByDesc('s.started_at')
            ->first(['srv.hostname']);

        return $row?->hostname;
    }

    protected function formatNumber(int $n): string
    {
        if ($n >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
        }
        if ($n >= 1_000) {
            return rtrim(rtrim(number_format($n / 1_000, 1), '0'), '.') . 'k';
        }
        return (string) $n;
    }
}
