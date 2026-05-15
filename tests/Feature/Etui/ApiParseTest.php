<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('returns a serialised AST for valid .menu source', function () {
    $response = $this->postJson(route('etui.api.parse'), [
        'source' => "menuDef {\n  name \"main\"\n  rect 16 16 608 448\n}",
        'mod' => 'etmain',
    ]);

    $response->assertOk();
    $body = $response->json();

    expect($body)->toHaveKey('menus');
    expect($body)->toHaveKey('errors');
    expect($body['errors'])->toBeEmpty();
    expect($body['menus'])->toHaveCount(1);
    expect($body['menus'][0]['kind'])->toBe('menu');
    expect($body['menus'][0]['children'][0]['kind'])->toBe('property');
    expect($body['menus'][0]['children'][0]['name'])->toBe('name');
});

it('returns errors for invalid source rather than failing the request', function () {
    $response = $this->postJson(route('etui.api.parse'), [
        'source' => "GARBAGE_AT_TOP_LEVEL",
        'mod' => 'etmain',
    ]);

    $response->assertOk();
    expect($response->json('errors'))->not->toBeEmpty();
    expect($response->json('errors.0.severity'))->toBe('error');
});

it('rejects missing source field with 422 validation error', function () {
    $response = $this->postJson(route('etui.api.parse'), [
        'mod' => 'etmain',
    ]);

    $response->assertStatus(422);
});

it('defaults to etmain profile when mod is omitted', function () {
    $response = $this->postJson(route('etui.api.parse'), [
        'source' => 'menuDef { name "x" }',
    ]);

    $response->assertOk();
    expect($response->json('errors'))->toBeEmpty();
});
