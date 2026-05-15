<?php

declare(strict_types=1);

use App\Models\Etui\EtuiMod;
use App\Models\Etui\EtuiProject;
use App\Models\User;
use Database\Seeders\EtuiModSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    (new EtuiModSeeder())->run();
});

it('redirects unauthenticated users to login from /etui/edit', function () {
    $response = $this->get('/etui/edit');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login');
});

it('renders the editor for an authenticated user on /etui/edit (new project)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/etui/edit');

    $response->assertOk();
    $response->assertSee('etui-editor');
    $response->assertSee('etui-preview');
});

it('stores a new project and redirects to its edit URL', function () {
    $user = User::factory()->create();
    $mod = EtuiMod::where('slug', 'etmain')->first();

    $response = $this->actingAs($user)->post('/etui/projects', [
        'mod_id' => $mod->id,
        'name' => 'Phase 3 Test Project',
        'content' => 'menuDef { name "t" }',
    ]);

    $response->assertRedirect();
    $project = EtuiProject::where('user_id', $user->id)->where('name', 'Phase 3 Test Project')->first();
    expect($project)->not->toBeNull();
    expect($project->share_token)->toHaveLength(32);
    expect($response->headers->get('Location'))->toContain("/etui/edit/{$project->id}");
});

it('rejects update from a user who does not own the project', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $project = EtuiProject::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($stranger)->put("/etui/projects/{$project->id}", [
        'content' => 'menuDef { name "hijack" }',
    ]);

    $response->assertForbidden();
});

it('creates a new revision via the autosave endpoint when content changes', function () {
    $user = User::factory()->create();
    $project = EtuiProject::factory()->create([
        'user_id' => $user->id,
        'content' => 'menuDef { name "v1" }',
    ]);
    expect($project->revisions()->count())->toBe(0);

    $response = $this->actingAs($user)->postJson('/api/etui/autosave', [
        'project_id' => $project->id,
        'content' => 'menuDef { name "v2" }',
    ]);

    $response->assertOk();
    expect($project->fresh()->revisions()->count())->toBe(1);
    expect($project->fresh()->revisions()->first()->content)->toBe('menuDef { name "v1" }');
    expect($project->fresh()->content)->toBe('menuDef { name "v2" }');
});

it('skips revision + content update when autosave receives identical content', function () {
    $user = User::factory()->create();
    $project = EtuiProject::factory()->create([
        'user_id' => $user->id,
        'content' => 'menuDef { name "same" }',
    ]);

    $this->actingAs($user)->postJson('/api/etui/autosave', [
        'project_id' => $project->id,
        'content' => 'menuDef { name "same" }',
    ])->assertOk();

    expect($project->fresh()->revisions()->count())->toBe(0);
});
