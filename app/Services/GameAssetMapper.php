<?php

namespace App\Services;

/**
 * Maps a file's "game" value to the corresponding public asset folder
 * for the 3D BSP viewer's standard-texture fallback pool.
 */
class GameAssetMapper
{
    /** Single source of truth - game string from files.game maps to folder name */
    private const MAP = [
        'ET'             => 'et-assets',
        'ETFortress'     => 'et-assets',
        'ET-Domination'  => 'et-assets',
        'RtCW'           => 'rtcw-assets',
        // 'ET Quake Wars' uses MegaTexture format - no Q3 BSP viewer support
        // 'Movies' are not maps
    ];

    public const DEFAULT_FOLDER = 'et-assets';

    public static function folderFor(?string $game): ?string
    {
        if ($game === null || $game === '') {
            return self::DEFAULT_FOLDER;
        }
        return self::MAP[$game] ?? null;
    }

    public static function urlFor(?string $game): ?string
    {
        $folder = self::folderFor($game);
        return $folder ? '/' . $folder : null;
    }

    public static function filesystemPath(?string $game, string $path): ?string
    {
        $folder = self::folderFor($game);
        if (!$folder) {
            return null;
        }
        $path = ltrim($path, '/');
        if (str_contains($path, '..')) {
            return null;
        }
        return public_path($folder . '/' . $path);
    }

    /** All known games that have asset pool support */
    public static function supportedGames(): array
    {
        return array_keys(self::MAP);
    }
}
