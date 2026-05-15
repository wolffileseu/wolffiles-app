<?php

declare(strict_types=1);

use App\Etui\FileSourceResolver;
use App\Etui\Profiles\EtMainProfile;
use App\Models\Etui\EtuiFile;
use App\Models\Etui\EtuiMod;
use App\Models\Etui\EtuiProject;
use App\Models\User;
use Database\Seeders\EtuiModSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// DatabaseTransactions keeps the production-dump rows in wolffiles_local
// safe — every test runs in a transaction that gets rolled back, so seeded
// fixtures and factory inserts never persist past the test boundary.
uses(DatabaseTransactions::class);

it('seeds 9 mods with proper slugs', function () {
    (new EtuiModSeeder())->run();

    expect(EtuiMod::count())->toBeGreaterThanOrEqual(9);
    expect(EtuiMod::pluck('slug')->toArray())->toContain(
        'etmain', 'etlegacy', 'etjump', 'silent',
        'jaymod', 'nq', 'nitmod', 'tce', 'etc',
    );
});

it('imports the 135 stock fixtures as stock files without duplicates', function () {
    (new EtuiModSeeder())->run();

    // Wipe any pre-seeded files inside the transaction so we measure THIS
    // run's import counts. The TRUNCATE is also rolled back on test end.
    EtuiFile::where('source', 'stock')->delete();

    $first = new Database\Seeders\EtuiFileFromFixturesSeeder();
    $first->run();
    $importedFirst = EtuiFile::stock()->count();

    // Second run is a no-op: hash-based dedup catches every fixture.
    $first->run();
    $importedSecond = EtuiFile::stock()->count();

    expect($importedFirst)->toBe(135);
    expect($importedSecond)->toBe(135);
});

it('reparses a file and updates parse_status, error_count and parsed_at', function () {
    $mod = EtuiMod::factory()->create(['parser_profile' => EtMainProfile::class]);
    $clean = EtuiFile::factory()->create([
        'mod_id' => $mod->id,
        'content' => 'menuDef { name "x" }',
    ]);

    $clean->reparse();
    expect($clean->parse_status)->toBe('clean');
    expect($clean->parse_error_count)->toBe(0);
    expect($clean->parsed_at)->not->toBeNull();

    $broken = EtuiFile::factory()->create([
        'mod_id' => $mod->id,
        'content' => 'GARBAGE_TOKEN_AT_TOP_LEVEL',
    ]);
    $broken->reparse();
    expect($broken->parse_status)->toBe('errors');
    expect($broken->parse_error_count)->toBeGreaterThan(0);
});

it('generates a unique share_token on every project create', function () {
    $tokens = [];
    foreach (range(1, 5) as $i) {
        $project = EtuiProject::factory()->create();
        expect($project->share_token)->toBeString()->toHaveLength(32);
        $tokens[] = $project->share_token;
    }
    expect(array_unique($tokens))->toHaveCount(5);
});

it('snapshot() creates an immutable revision row carrying current content', function () {
    $project = EtuiProject::factory()->create([
        'content' => 'menuDef { name "v1" }',
    ]);

    $rev = $project->snapshot();
    expect($rev->content)->toBe('menuDef { name "v1" }');
    expect($rev->project_id)->toBe($project->id);
    expect($project->revisions()->count())->toBe(1);

    $project->update(['content' => 'menuDef { name "v2" }']);
    $project->snapshot();
    expect($project->revisions()->count())->toBe(2);
});

it('cascades revision deletion when the parent project is deleted', function () {
    $project = EtuiProject::factory()->create();
    $project->revisions()->createMany([
        ['content' => 'a'], ['content' => 'b'], ['content' => 'c'],
    ]);
    $revIds = $project->revisions()->pluck('id');
    expect($revIds)->toHaveCount(3);

    $project->delete();

    expect(App\Models\Etui\EtuiRevision::whereIn('id', $revIds)->count())->toBe(0);
});
