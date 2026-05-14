<?php

declare(strict_types=1);

use App\Etui\Ast\EventBlockNode;
use App\Etui\Ast\ItemDefNode;
use App\Etui\Ast\MenuDefNode;
use App\Etui\Ast\Node;
use App\Etui\Ast\PropertyNode;
use App\Etui\Lexer;
use App\Etui\MacroExpander;
use App\Etui\ParseResult;
use App\Etui\Parser;
use App\Etui\Preprocessor;
use App\Etui\Profiles\EtMainProfile;
use App\Etui\Profiles\MacroDefinition;
use App\Etui\Profiles\ModProfile;
use App\Etui\SourceResolver;

final class ExpanderEmptyResolver implements SourceResolver
{
    public function resolve(string $path): ?string
    {
        return null;
    }
}

/**
 * Full Preprocessor -> Lexer -> Parser -> MacroExpander pipeline.
 * @param array<string, string|int> $predefined
 */
function expandPipeline(string $source, ?ModProfile $profile = null, array $predefined = []): ParseResult
{
    $profile ??= new EtMainProfile();
    $ppResult = (new Preprocessor(new ExpanderEmptyResolver()))->process($source, $predefined);
    $tokens = (new Lexer($profile->supportsI18nWrapper()))->tokenize($ppResult->source);
    $parsed = (new Parser())->parse($tokens);
    return (new MacroExpander($profile))->expand($parsed, $ppResult->localMacros);
}

function findProperty(MenuDefNode|ItemDefNode $node, string $name): ?PropertyNode
{
    foreach ($node->children as $c) {
        if ($c instanceof PropertyNode && $c->name === $name) {
            return $c;
        }
    }
    return null;
}

function findEventBlock(ItemDefNode $node, string $name): ?EventBlockNode
{
    foreach ($node->children as $c) {
        if ($c instanceof EventBlockNode && $c->name === $name) {
            return $c;
        }
    }
    return null;
}

it('expands a simple BUTTON macro into an ItemDefNode with token-pasted item name', function () {
    $result = expandPipeline(
        'menuDef { BUTTON(10, 20, 100, 30, "Quit", .3, 14, close quit) }',
    );

    expect($result->errors->isEmpty())->toBeTrue();

    $menu = $result->menus[0];
    expect($menu->children)->toHaveCount(1);

    $item = $menu->children[0];
    expect($item)->toBeInstanceOf(ItemDefNode::class);

    // ## token-paste merged "bttn" and "Quit" into a single STRING.
    $name = findProperty($item, 'name');
    expect($name)->not->toBeNull();
    expect($name->values[0]->value)->toBe('bttnQuit');

    // Coordinate parameters survived substitution as NUMBER tokens.
    $rect = findProperty($item, 'rect');
    expect($rect->values[0]->value)->toBe(10);
    expect($rect->values[1]->value)->toBe(20);
    expect($rect->values[2]->value)->toBe(100);
    expect($rect->values[3]->value)->toBe(30);
});

it('preserves macro args containing semicolons inside the expanded action body', function () {
    $result = expandPipeline(
        'menuDef { BUTTON(10, 20, 100, 30, "Quit", .3, 14, close quit ; open main) }',
    );

    $item = $result->menus[0]->children[0];
    $action = findEventBlock($item, 'action');
    expect($action)->not->toBeNull();

    // The BUTTON_ACTION arg [close, quit, ;, open, main] must land at the tail
    // of the action body, with the SEMICOLON intact as a separator token.
    $bodyValues = array_map(fn ($t) => $t->value ?? $t->lexeme, $action->body);
    expect($bodyValues)->toContain('close');
    expect($bodyValues)->toContain('quit');
    expect($bodyValues)->toContain('open');
    expect($bodyValues)->toContain('main');

    $hasSemicolon = false;
    foreach ($action->body as $tok) {
        if ($tok->type === App\Etui\TokenType::SEMICOLON) {
            $hasSemicolon = true;
            break;
        }
    }
    expect($hasSemicolon)->toBeTrue();
});

it('records an error for an unknown macro without crashing the rest of the parse', function () {
    $result = expandPipeline('menuDef { NONEXISTENT_THING(1, 2, 3) }');

    expect($result->errors->hasErrors())->toBeTrue();
    expect($result->menus[0]->children)->toBeEmpty();
});

it('handles the FUI toggle via Profile-defined symbols at preprocessor level', function () {
    // Source mirrors menumacros.h's WINDOW alias dispatcher. Without FUI the
    // alias resolves to WINDOW_INGAME (backcolor alpha .6); with FUI=1 it
    // resolves to WINDOW_FUI (backcolor alpha .2). Both bodies live in
    // EtMainProfile::macros() — the test proves the toggle plumbs all the
    // way from preprocessor symbols to a measurable AST difference.
    $source = "#ifdef FUI\n#define WINDOW WINDOW_FUI\n#else\n#define WINDOW WINDOW_INGAME\n#endif\nmenuDef { WINDOW(\"Main\", 90) }";

    $ingame = expandPipeline($source);
    $fui = expandPipeline($source, predefined: ['FUI' => '1']);

    $ingameWindow = $ingame->menus[0]->children[0];
    $fuiWindow = $fui->menus[0]->children[0];

    expect($ingameWindow)->toBeInstanceOf(ItemDefNode::class);
    expect($fuiWindow)->toBeInstanceOf(ItemDefNode::class);

    // backcolor is "0 0 0 .6" vs "0 0 0 .2" — alpha is the differentiator.
    $ingameBg = findProperty($ingameWindow, 'backcolor');
    $fuiBg = findProperty($fuiWindow, 'backcolor');
    expect($ingameBg->values[3]->value)->toBe(0.6);
    expect($fuiBg->values[3]->value)->toBe(0.2);
});

it('expands SUBWINDOW with coordinate arithmetic preserved as raw token sequence', function () {
    $result = expandPipeline(
        'menuDef { SUBWINDOW(10, 20, 100, 50, "Pane") }',
    );

    $menu = $result->menus[0];
    expect($menu->children)->toHaveCount(2);

    // Second itemDef carries the arithmetic in its rect:
    //   rect SUBWINDOW_X+2 SUBWINDOW_Y+2 SUBWINDOW_W-4 12
    //   -> rect 10+2 20+2 100-4 12
    $titleItem = $menu->children[1];
    $rect = findProperty($titleItem, 'rect');

    $types = array_map(fn ($t) => $t->type, $rect->values);
    expect($types[1])->toBe(App\Etui\TokenType::PLUS);
    expect($types[4])->toBe(App\Etui\TokenType::PLUS);
    expect($types[7])->toBe(App\Etui\TokenType::MINUS);

    $values = array_map(fn ($t) => $t->value, array_values(array_filter(
        $rect->values,
        fn ($t) => $t->type === App\Etui\TokenType::NUMBER,
    )));
    expect($values)->toBe([10, 2, 20, 2, 100, 4, 12]);
});

it('applies ## token-paste merging neighbouring tokens into a single STRING', function () {
    // Custom stub profile keeps the test focused on the paste mechanism
    // without the surrounding ceremony of a full menumacros.h-style macro.
    $profile = new class extends ModProfile {
        public function slug(): string { return 'test'; }
        public function macros(): array {
            return [
                'GREET' => new MacroDefinition(
                    'GREET',
                    ['NAME'],
                    'name "Hello"##NAME',
                ),
            ];
        }
    };

    $result = expandPipeline('menuDef { GREET("World") }', profile: $profile);

    expect($result->errors->isEmpty())->toBeTrue();
    $prop = $result->menus[0]->children[0];
    expect($prop)->toBeInstanceOf(PropertyNode::class);
    expect($prop->name)->toBe('name');
    expect($prop->values[0]->type)->toBe(App\Etui\TokenType::STRING);
    expect($prop->values[0]->value)->toBe('HelloWorld');
});

it('lets a local #define override the profile-provided macro of the same name', function () {
    // EtMainProfile ships a complex BUTTON. The source overrides it with
    // a one-line definition; the MacroExpander must prefer the local one.
    $source = <<<'MENU'
#define BUTTON(text) itemDef { name text }
menuDef { BUTTON("Hi") }
MENU;

    $result = expandPipeline($source);

    $menu = $result->menus[0];
    expect($menu->children)->toHaveCount(1);

    $item = $menu->children[0];
    expect($item)->toBeInstanceOf(ItemDefNode::class);
    expect($item->children)->toHaveCount(1);

    $name = $item->children[0];
    expect($name)->toBeInstanceOf(PropertyNode::class);
    expect($name->name)->toBe('name');
    expect($name->values[0]->value)->toBe('Hi');
});
