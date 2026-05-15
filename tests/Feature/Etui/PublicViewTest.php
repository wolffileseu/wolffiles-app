<?php

declare(strict_types=1);

use App\Models\Etui\EtuiFile;
use App\Models\Etui\EtuiMod;
use Database\Seeders\EtuiModSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // /etui depends on at least the 9 stock mods existing — re-run the
    // seeder inside the transaction so the production-dump rows in the
    // dev DB don't bleed into assertions.
    (new EtuiModSeeder())->run();
});

it('renders the mod index at /etui', function () {
    $response = $this->get('/etui');

    $response->assertOk();
    $response->assertSee('etmain');
    $response->assertSee('etlegacy');
});

it('renders the file list for /etui/etmain', function () {
    $mod = EtuiMod::where('slug', 'etmain')->first();
    EtuiFile::factory()->stock()->create([
        'mod_id' => $mod->id,
        'name' => 'phase3_smoke.menu',
        'content' => 'menuDef { name "smoke" }',
    ]);

    $response = $this->get('/etui/etmain');

    $response->assertOk();
    $response->assertSee('phase3_smoke.menu');
});

it('renders the read-only viewer with AST payload at /etui/{mod}/{file}', function () {
    $mod = EtuiMod::where('slug', 'etmain')->first();
    EtuiFile::factory()->stock()->create([
        'mod_id' => $mod->id,
        'name' => 'phase3_read.menu',
        'content' => 'menuDef { name "read" rect 16 16 608 448 }',
    ]);

    $response = $this->get('/etui/etmain/phase3_read.menu');

    $response->assertOk();
    $response->assertSee('etui-preview');
    $response->assertSeeText('parsed cleanly');
});

it('404s a non-existent mod slug rather than rendering an empty page', function () {
    $response = $this->get('/etui/no_such_mod_existed');

    $response->assertNotFound();
});
