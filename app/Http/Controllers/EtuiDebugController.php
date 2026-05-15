<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Etui\FileSourceResolver;
use App\Etui\MenuFile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only debug viewer over the ETUI parser pipeline. Phase 1 utility —
 * lists the stock .menu fixtures, parses each on demand and dumps the
 * resulting AST shape so the parser can be eyeballed without combing
 * through Pest output. Phase 4 replaces this with the proper public
 * editor + renderer under /etui.
 */
final class EtuiDebugController extends Controller
{
    private const ALLOWED_MODS = ['etmain', 'etlegacy'];

    public function index()
    {
        $files = [];
        foreach (self::ALLOWED_MODS as $mod) {
            $paths = glob(base_path("tests/fixtures/etui/{$mod}/*.menu")) ?: [];
            $names = array_map('basename', $paths);
            sort($names);
            $files[$mod] = $names;
        }

        return view('etui-debug.index', ['files' => $files]);
    }

    public function show(string $mod, string $file)
    {
        if (! in_array($mod, self::ALLOWED_MODS, true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $path = base_path("tests/fixtures/etui/{$mod}/{$file}");
        if (! is_file($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $source = file_get_contents($path);

        // FileSourceResolver needs absolute search paths — the profile's relative
        // hints (e.g. '_includes/etlegacy/') are useful for production where a
        // proper base is configured, but here we wire the fixture root directly.
        $includes = $mod === 'etlegacy'
            ? [base_path('tests/fixtures/etui/_includes/etlegacy/'), base_path('tests/fixtures/etui/_includes/')]
            : [base_path('tests/fixtures/etui/_includes/')];
        $resolver = new FileSourceResolver($includes);

        $result = MenuFile::parse($source, $mod, $resolver);

        // Source-declared #include lines — the preprocessor inlines them, so
        // by the time MenuFile returns, the AST has no separate include list.
        // A regex over the raw source surfaces the user's intent for display.
        preg_match_all('/^\s*#include\s+"([^"]+)"/m', $source, $m);
        $declaredIncludes = $m[1] ?? [];

        return view('etui-debug.show', [
            'mod' => $mod,
            'file' => $file,
            'source' => $source,
            'result' => $result,
            'declaredIncludes' => $declaredIncludes,
        ]);
    }
}
