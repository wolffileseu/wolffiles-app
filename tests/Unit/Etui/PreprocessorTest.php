<?php

declare(strict_types=1);

use App\Etui\Preprocessor;
use App\Etui\SourceResolver;

/**
 * In-memory resolver — keeps preprocessor tests free from filesystem deps.
 * Filesystem behaviour is exercised separately in FileSourceResolver tests.
 */
final class PreprocessorInMemoryResolver implements SourceResolver
{
    /** @param array<string, string> $files */
    public function __construct(private array $files = []) {}

    public function resolve(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }
}

/**
 * @param array<string, string> $files
 * @param array<string, string|int> $predefined
 */
function pp(string $source, array $files = [], array $predefined = []): string
{
    $resolver = new PreprocessorInMemoryResolver($files);
    return (new Preprocessor($resolver))->process($source, $predefined);
}

it('passes through source without directives unchanged', function () {
    $out = pp("name \"x\"\nrect 0 0 640 480");

    expect($out)->toContain('name "x"');
    expect($out)->toContain('rect 0 0 640 480');
});

it('substitutes object-like #defines in subsequent code', function () {
    $out = pp("#define WIDTH 128\n#define HEIGHT 96\nrect WIDTH HEIGHT 0 0");

    expect($out)->toContain('rect 128 96 0 0');
    expect($out)->not->toContain('#define');
});

it('resolves #include via the SourceResolver', function () {
    $out = pp(
        source: "#include \"inc.h\"\nrect WIDTH 0 0 0",
        files: ['inc.h' => '#define WIDTH 256'],
    );

    expect($out)->toContain('rect 256 0 0 0');
});

it('reports an error on unresolvable #include', function () {
    $resolver = new PreprocessorInMemoryResolver([]);
    $pp = new Preprocessor($resolver);

    $pp->process("#include \"missing.h\"\n");

    expect($pp->errors()->hasErrors())->toBeTrue();
    expect($pp->errors()->count())->toBe(1);
});

it('strips function-like #define directives entirely', function () {
    $out = pp(
        "#define BTN(x, y, w, h, t) itemDef { name t rect x y w h }\nBTN( 1, 2, 3, 4, \"Quit\" )",
    );

    expect($out)->toContain('BTN( 1, 2, 3, 4, "Quit" )');
    expect($out)->not->toContain('itemDef { name t');
});

it('handles #ifdef when symbol is defined', function () {
    $source = "#ifdef FUI\nA\n#else\nB\n#endif";

    $out = pp(source: $source, predefined: ['FUI' => '1']);

    expect(trim(preg_replace('/\s+/', ' ', $out)))->toBe('A');
});

it('handles #ifdef when symbol is undefined — emits #else branch', function () {
    $source = "#ifdef FUI\nA\n#else\nB\n#endif";

    $out = pp($source);

    expect(trim(preg_replace('/\s+/', ' ', $out)))->toBe('B');
});

it('resolves the WINDOW alias via #ifdef per menumacros.h pattern', function () {
    $source = <<<'MENU'
#ifdef FUI
#define WINDOW WINDOW_FUI
#else
#define WINDOW WINDOW_INGAME
#endif
WINDOW("Main", 90)
MENU;

    $vanilla = pp($source);
    expect($vanilla)->toContain('WINDOW_INGAME("Main", 90)');
    expect($vanilla)->not->toContain('WINDOW_FUI');

    $fui = pp($source, predefined: ['FUI' => '1']);
    expect($fui)->toContain('WINDOW_FUI("Main", 90)');
    expect($fui)->not->toContain('WINDOW_INGAME');
});
