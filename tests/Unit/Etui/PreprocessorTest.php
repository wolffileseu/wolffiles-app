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
    return (new Preprocessor($resolver))->process($source, $predefined)->source;
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

it('removes function-like #define directives from source and exposes them as local macros', function () {
    $resolver = new PreprocessorInMemoryResolver();
    $pp = new Preprocessor($resolver);
    $result = $pp->process(
        "#define BTN(x, y, w, h, t) itemDef { name t rect x y w h }\nBTN( 1, 2, 3, 4, \"Quit\" )",
    );

    expect($result->source)->toContain('BTN( 1, 2, 3, 4, "Quit" )');
    expect($result->source)->not->toContain('itemDef { name t');
    expect($result->localMacros)->toHaveKey('BTN');
    expect($result->localMacros['BTN']->paramNames)->toBe(['x', 'y', 'w', 'h', 't']);
    expect($result->localMacros['BTN']->bodyTemplate)->toContain('itemDef');
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

it('detects indented #define directives (real wm_quickmessage.menu pattern)', function () {
    // The wm_*.menu fixtures put their #define lines after a tab:
    //     \t#define DEFAULT_TEXT_SCALE 0.25
    // The directive detector ltrim()-s the line before the '#' check so
    // these are picked up just like a left-aligned directive.
    $out = pp("\t#define X 5\nname X\n");

    expect($out)->toContain('name 5');
});

it('treats a valueless #define as a defined symbol for #ifdef', function () {
    // wm_quickmessage.menu line 7:
    //     #define QM_MENU_GRADIENT_START_OFFSET
    // (used purely as an #ifdef sentinel, the value never matters)
    $out = pp("#define X\n#ifdef X\nyes\n#endif\n");

    expect(trim(preg_replace('/\s+/', ' ', $out)))->toBe('yes');
});

it('expands nested $evalint depth-first against substituted #defines', function () {
    // Outer $evalint depends on the inner one resolving to a number first.
    // Mirrors how menumacros.h composes layouts from primitive #defines.
    $out = pp(
        "#define WIDTH 100\nrect \$evalint(2 + \$evalint(WIDTH / 4)) 0 0 0",
    );

    expect($out)->toContain('rect 27 0 0 0');
});

it('errors on a non-constant $evalint and leaves the directive intact', function () {
    $resolver = new PreprocessorInMemoryResolver();
    $pp = new Preprocessor($resolver);

    $out = $pp->process("rect \$evalint(UNDEFINED_THING + 5) 0 0 0");

    expect($pp->errors()->hasErrors())->toBeTrue();
    expect($pp->errors()->count())->toBe(1);
    // Directive must survive in the output so the editor's linter can point
    // at the offending source position rather than the now-deleted bytes.
    expect($out->source)->toContain('$evalint(UNDEFINED_THING + 5)');
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
