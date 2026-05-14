<?php

declare(strict_types=1);

namespace App\Etui;

final class FileSourceResolver implements SourceResolver
{
    /** @var string[] */
    private readonly array $searchPaths;

    /**
     * @param  string|string[]  $searchPaths  Absolute search directories tried in order.
     */
    public function __construct(string|array $searchPaths)
    {
        $paths = is_array($searchPaths) ? $searchPaths : [$searchPaths];
        $this->searchPaths = array_values(array_map(
            static fn (string $p): string => rtrim($p, "/\\"),
            $paths,
        ));
    }

    public function resolve(string $path): ?string
    {
        $relative = ltrim($path, "/\\");
        if ($relative === '' || str_contains($relative, "\0")) {
            return null;
        }

        foreach ($this->searchPaths as $base) {
            $candidate = $base . DIRECTORY_SEPARATOR . $relative;
            $real = realpath($candidate);
            if ($real === false) {
                continue;
            }
            $baseReal = realpath($base);
            if ($baseReal === false) {
                continue;
            }
            // Reject path traversal that escapes the search root.
            if ($real !== $baseReal && ! str_starts_with($real, $baseReal . DIRECTORY_SEPARATOR)) {
                continue;
            }
            if (! is_file($real) || ! is_readable($real)) {
                continue;
            }
            $contents = file_get_contents($real);
            return $contents === false ? null : $contents;
        }

        return null;
    }
}
