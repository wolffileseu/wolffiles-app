<?php

declare(strict_types=1);

use App\Etui\Errors\ParseError;
use App\Etui\FileSourceResolver;
use App\Etui\MenuFile;
use PHPUnit\Framework\AssertionFailedError;

// Lives under tests/Unit/ rather than tests/Feature/ so Pest doesn't boot
// the Laravel TestCase for each of the ~135 fixture parses — these golden
// tests are pure domain-layer integration and have no app dependencies.
function fixtureRoot(): string
{
    return realpath(__DIR__ . '/../../fixtures/etui') ?: '';
}

/**
 * @return array<string, array{0: string, 1: string}>  basename => [path, modSlug]
 */
function goldenFixtureDataset(string $modSlug): array
{
    $files = glob(fixtureRoot() . '/' . $modSlug . '/*.menu') ?: [];
    $dataset = [];
    foreach ($files as $path) {
        $dataset[basename($path)] = [$path, $modSlug];
    }
    return $dataset;
}

function assertParsesCleanly(string $path, string $modSlug): void
{
    // EtLegacy profile gets its own include search path layered in front;
    // FileSourceResolver iterates them in order so an etlegacy-specific
    // header wins over the vanilla one when both exist.
    $includes = $modSlug === 'etlegacy'
        ? [fixtureRoot() . '/_includes/etlegacy/', fixtureRoot() . '/_includes/']
        : [fixtureRoot() . '/_includes/'];

    $resolver = new FileSourceResolver($includes);
    $source = file_get_contents($path);
    $result = MenuFile::parse($source, $modSlug, $resolver);

    $hardErrors = array_values(array_filter(
        $result->errors->all(),
        fn (ParseError $e) => $e->severity === ParseError::SEVERITY_ERROR,
    ));

    if ($hardErrors !== []) {
        $sample = array_slice($hardErrors, 0, 3);
        $lines = array_map(
            fn (ParseError $e) => sprintf('    L%d: %s', $e->line, $e->message),
            $sample,
        );
        throw new AssertionFailedError(sprintf(
            "%s: %d parse error%s (showing first %d):\n%s",
            basename($path),
            count($hardErrors),
            count($hardErrors) === 1 ? '' : 's',
            count($sample),
            implode("\n", $lines),
        ));
    }

    expect($result->menus)->not->toBeEmpty(
        basename($path).': no menuDef blocks recognised',
    );
}

it('parses ETMain fixture without errors', function (string $path, string $modSlug) {
    assertParsesCleanly($path, $modSlug);
})->with(fn () => goldenFixtureDataset('etmain'));

it('parses ETLegacy fixture without errors', function (string $path, string $modSlug) {
    assertParsesCleanly($path, $modSlug);
})->with(fn () => goldenFixtureDataset('etlegacy'));
