<?php

declare(strict_types=1);

use App\Etui\Ast\EventBlockNode;
use App\Etui\Ast\ItemDefNode;
use App\Etui\Ast\MacroCallNode;
use App\Etui\Ast\MenuDefNode;
use App\Etui\Ast\PropertyNode;
use App\Etui\Lexer;
use App\Etui\ParseResult;
use App\Etui\Parser;
use App\Etui\TokenType;

function parseSrc(string $src): ParseResult
{
    $tokens = (new Lexer())->tokenize($src);
    return (new Parser())->parse($tokens);
}

it('returns no menus and no errors for empty source', function () {
    $result = parseSrc('');

    expect($result->menus)->toBeEmpty();
    expect($result->errors->isEmpty())->toBeTrue();
});

it('parses a menuDef with a simple name property', function () {
    $result = parseSrc("menuDef {\n  name \"main\"\n}");

    expect($result->menus)->toHaveCount(1);
    expect($result->menus[0])->toBeInstanceOf(MenuDefNode::class);
    expect($result->errors->isEmpty())->toBeTrue();

    $prop = $result->menus[0]->children[0];
    expect($prop)->toBeInstanceOf(PropertyNode::class);
    expect($prop->name)->toBe('name');
    expect($prop->values)->toHaveCount(1);
    expect($prop->values[0]->value)->toBe('main');
});

it('captures all tokens of a multi-value property on the same line', function () {
    $result = parseSrc("menuDef {\n  rect 16 16 608 448\n}");

    $prop = $result->menus[0]->children[0];
    expect($prop->name)->toBe('rect');
    expect(array_map(fn ($t) => $t->value, $prop->values))->toBe([16, 16, 608, 448]);
});

it('parses an itemDef nested inside a menuDef', function () {
    $result = parseSrc("menuDef {\n  itemDef {\n    name \"title\"\n  }\n}");

    $menu = $result->menus[0];
    expect($menu->children)->toHaveCount(1);

    $item = $menu->children[0];
    expect($item)->toBeInstanceOf(ItemDefNode::class);
    expect($item->children)->toHaveCount(1);
    expect($item->children[0])->toBeInstanceOf(PropertyNode::class);
    expect($item->children[0]->name)->toBe('name');
});

it('captures event-block bodies as raw brace-balanced tokens', function () {
    $result = parseSrc("menuDef {\n  onESC {\n    close quit ; open main\n  }\n}");

    $event = $result->menus[0]->children[0];
    expect($event)->toBeInstanceOf(EventBlockNode::class);
    expect($event->name)->toBe('onESC');
    expect($event->body)->toHaveCount(5);
    expect($event->body[0]->value)->toBe('close');
    expect($event->body[1]->value)->toBe('quit');
    expect($event->body[2]->type)->toBe(TokenType::SEMICOLON);
    expect($event->body[3]->value)->toBe('open');
    expect($event->body[4]->value)->toBe('main');
});

it('parses macro calls and splits args at comma in paren-depth 1', function () {
    $result = parseSrc('menuDef { BUTTON( 10, 20, "Quit", .3 ) }');

    $macro = $result->menus[0]->children[0];
    expect($macro)->toBeInstanceOf(MacroCallNode::class);
    expect($macro->name)->toBe('BUTTON');
    expect($macro->args)->toHaveCount(4);
    expect($macro->args[0][0]->value)->toBe(10);
    expect($macro->args[1][0]->value)->toBe(20);
    expect($macro->args[2][0]->value)->toBe('Quit');
    expect($macro->args[3][0]->value)->toBe(0.3);
});

it('keeps semicolons inside macro args as raw tokens — never as arg separators', function () {
    // Real ET pattern: last BUTTON arg is a UI-script statement list,
    // not a string. The Parser must capture it as raw Token[] so the
    // MacroExpander later hands it to the renderer untouched.
    $result = parseSrc('menuDef { BUTTON( 10, "Quit", close quit ; open main ) }');

    $macro = $result->menus[0]->children[0];
    expect($macro->args)->toHaveCount(3);
    expect($macro->args[2])->toHaveCount(5);
    expect($macro->args[2][0]->value)->toBe('close');
    expect($macro->args[2][2]->type)->toBe(TokenType::SEMICOLON);
    expect($macro->args[2][4]->value)->toBe('main');
});

it('parses multiple menuDef blocks into the menus list', function () {
    $result = parseSrc("menuDef { name \"a\" }\nmenuDef { name \"b\" }");

    expect($result->menus)->toHaveCount(2);
    expect($result->menus[0]->children[0]->values[0]->value)->toBe('a');
    expect($result->menus[1]->children[0]->values[0]->value)->toBe('b');
});

it('recovers from a garbage token at top level and parses the next menuDef', function () {
    $result = parseSrc("INVALID_GARBAGE\nmenuDef { name \"recovered\" }");

    expect($result->errors->hasErrors())->toBeTrue();
    expect($result->menus)->toHaveCount(1);
    expect($result->menus[0]->children[0]->values[0]->value)->toBe('recovered');
});

it('preserves arithmetic operator tokens inside property values', function () {
    // Post-preprocessor source can carry expressions like `WIDTH-12` when no
    // $eval directive was wrapped around them. The Parser keeps them as raw
    // Token[] for a later evaluator with profile context.
    $result = parseSrc("menuDef {\n  rect 128-12 0 0 0\n}");

    $prop = $result->menus[0]->children[0];
    expect($prop->values)->toHaveCount(6);
    expect($prop->values[0]->value)->toBe(128);
    expect($prop->values[1]->type)->toBe(TokenType::MINUS);
    expect($prop->values[2]->value)->toBe(12);
});

it('parses flag-style properties without values (popup, decoration)', function () {
    $result = parseSrc("menuDef {\n  popup\n  fadeClamp 0.5\n}");

    $menu = $result->menus[0];
    expect($menu->children)->toHaveCount(2);

    $popup = $menu->children[0];
    expect($popup)->toBeInstanceOf(PropertyNode::class);
    expect($popup->name)->toBe('popup');
    expect($popup->values)->toBeEmpty();

    $fade = $menu->children[1];
    expect($fade->name)->toBe('fadeClamp');
    expect($fade->values)->toHaveCount(1);
    expect($fade->values[0]->value)->toBe(0.5);
});
