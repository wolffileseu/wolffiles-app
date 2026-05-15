<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $mod }}/{{ $file }} — ETUI Debug</title>
    <style>
        body { margin: 0; padding: 0; background: #111827; color: #d1d5db; font: 13px/1.55 ui-monospace, SFMono-Regular, Menlo, monospace; }
        header { padding: 16px 24px; background: #0f172a; border-bottom: 1px solid #1f2937; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        header a { color: #93c5fd; text-decoration: none; font-size: 12px; }
        header a:hover { color: #dbeafe; }
        header .path { color: #f3f4f6; font-size: 15px; flex: 1; }
        header .badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; letter-spacing: .03em; }
        header .badge-ok { background: #064e3b; color: #6ee7b7; }
        header .badge-fail { background: #7f1d1d; color: #fca5a5; }
        main { display: grid; grid-template-columns: 1fr 1fr; gap: 0; min-height: calc(100vh - 60px); }
        .left { padding: 20px 24px; overflow-y: auto; border-right: 1px solid #1f2937; }
        .right { overflow: auto; background: #030712; }
        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin: 22px 0 8px; }
        h3:first-child { margin-top: 0; }
        .err { background: #2d0a0a; border-left: 3px solid #dc2626; padding: 8px 12px; margin: 0 0 6px; }
        .err-pos { color: #fca5a5; font-weight: bold; margin-right: 8px; }
        details { background: #1f2937; border: 1px solid #374151; border-radius: 4px; padding: 10px 12px; margin-bottom: 10px; }
        summary { cursor: pointer; color: #f3f4f6; outline: none; }
        summary .meta { color: #6b7280; font-weight: normal; margin-left: 10px; font-size: 11px; }
        table.props { border-collapse: collapse; width: 100%; margin: 10px 0 4px; }
        table.props td { padding: 3px 8px; vertical-align: top; border-top: 1px solid #1f2937; }
        table.props td.k { color: #93c5fd; width: 30%; }
        table.props td.v { color: #d1d5db; word-break: break-all; }
        ul.kids { list-style: none; padding-left: 0; margin: 8px 0 0; }
        ul.kids li { padding: 3px 0; color: #9ca3af; }
        ul.kids .name { color: #fcd34d; }
        ul.kids .count { color: #6b7280; font-size: 11px; }
        ul.incl { list-style: none; padding-left: 0; margin: 0; }
        ul.incl li { color: #9ca3af; padding: 2px 0; }
        ul.incl li::before { content: '#include '; color: #6b7280; }
        .none { color: #6b7280; font-style: italic; }
        pre.src { margin: 0; padding: 16px 18px; font: 12px/1.6 ui-monospace, Menlo, monospace; color: #cbd5e1; white-space: pre; }
        pre.src .ln { display: inline-block; width: 4ch; text-align: right; color: #4b5563; user-select: none; margin-right: 14px; }
    </style>
</head>
<body>
@php
    $findProp = function ($node, string $name) {
        foreach ($node->children as $c) {
            if ($c instanceof \App\Etui\Ast\PropertyNode && $c->name === $name) {
                return $c;
            }
        }
        return null;
    };
    $stringify = function (array $tokens): string {
        return implode(' ', array_map(function ($t) {
            if ($t->type === \App\Etui\TokenType::STRING) {
                return '"'.$t->value.'"';
            }
            return (string) ($t->value ?? $t->lexeme);
        }, $tokens));
    };
    $hardErrors = array_filter(
        $result->errors->all(),
        fn ($e) => $e->severity === \App\Etui\Errors\ParseError::SEVERITY_ERROR,
    );
    $lines = explode("\n", $source);
@endphp

<header>
    <a href="{{ route('etui-debug.index') }}">&larr; back to list</a>
    <span class="path">{{ $mod }} / <strong>{{ $file }}</strong></span>
    @if ($hardErrors === [])
        <span class="badge badge-ok">✓ parsed cleanly</span>
    @else
        <span class="badge badge-fail">✗ {{ count($hardErrors) }} error{{ count($hardErrors) === 1 ? '' : 's' }}</span>
    @endif
</header>

<main>
    <div class="left">
        @if ($hardErrors !== [])
            <h3>Parse Errors</h3>
            @foreach ($hardErrors as $e)
                <div class="err"><span class="err-pos">L{{ $e->line }}:{{ $e->col }}</span>{{ $e->message }}</div>
            @endforeach
        @endif

        <h3>Declared Includes</h3>
        @if ($declaredIncludes === [])
            <div class="none">none</div>
        @else
            <ul class="incl">
                @foreach ($declaredIncludes as $inc)
                    <li>"{{ $inc }}"</li>
                @endforeach
            </ul>
        @endif

        <h3>Menus ({{ count($result->menus) }})</h3>
        @if ($result->menus === [])
            <div class="none">no top-level constructs recognised</div>
        @endif
        @foreach ($result->menus as $idx => $menu)
            @php
                $nameProp = $findProp($menu, 'name');
                $name = $nameProp ? $stringify($nameProp->values) : '(unnamed)';
                $props = array_values(array_filter($menu->children, fn ($c) => $c instanceof \App\Etui\Ast\PropertyNode));
                $items = array_values(array_filter($menu->children, fn ($c) => $c instanceof \App\Etui\Ast\ItemDefNode));
                $events = array_values(array_filter($menu->children, fn ($c) => $c instanceof \App\Etui\Ast\EventBlockNode));
            @endphp
            <details {{ $idx === 0 ? 'open' : '' }}>
                <summary>{{ $name }} <span class="meta">{{ count($props) }} props · {{ count($items) }} items · {{ count($events) }} events</span></summary>
                @if ($props !== [])
                    <table class="props">
                        @foreach ($props as $p)
                            <tr>
                                <td class="k">{{ $p->name }}</td>
                                <td class="v">{!! $stringify($p->values) !== '' ? e($stringify($p->values)) : '<span class="none">(flag)</span>' !!}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
                @if ($items !== [])
                    <ul class="kids">
                        @foreach ($items as $i)
                            @php
                                $itemName = $findProp($i, 'name');
                                $iProps = count(array_filter($i->children, fn ($c) => $c instanceof \App\Etui\Ast\PropertyNode));
                                $iEvents = count(array_filter($i->children, fn ($c) => $c instanceof \App\Etui\Ast\EventBlockNode));
                            @endphp
                            <li>itemDef <span class="name">{{ $itemName ? $stringify($itemName->values) : '(unnamed)' }}</span> <span class="count">{{ $iProps }} props, {{ $iEvents }} events</span></li>
                        @endforeach
                    </ul>
                @endif
                @if ($events !== [])
                    <ul class="kids">
                        @foreach ($events as $ev)
                            <li>event <span class="name">{{ $ev->name }}</span> <span class="count">{{ count($ev->body) }} body tokens</span></li>
                        @endforeach
                    </ul>
                @endif
            </details>
        @endforeach
    </div>

    <div class="right">
        <pre class="src">@foreach ($lines as $i => $line)<span class="ln">{{ $i + 1 }}</span>{{ rtrim($line, "\r") }}
@endforeach</pre>
    </div>
</main>
</body>
</html>
