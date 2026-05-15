<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ETUI Debug — Fixtures</title>
    <style>
        body { margin: 0; padding: 32px; background: #111827; color: #d1d5db; font: 14px/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; }
        h1 { color: #f3f4f6; font-size: 22px; margin: 0 0 6px; }
        .lead { color: #9ca3af; margin: 0 0 28px; font-size: 13px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; max-width: 1100px; }
        .col h2 { font-size: 15px; color: #f3f4f6; margin: 0 0 12px; padding-bottom: 8px; border-bottom: 1px solid #374151; }
        .count { color: #6b7280; font-weight: normal; margin-left: 8px; }
        ul { list-style: none; padding: 0; margin: 0; }
        li { padding: 3px 0; }
        a { color: #93c5fd; text-decoration: none; }
        a:hover { color: #dbeafe; text-decoration: underline; }
        .footer { margin-top: 36px; color: #6b7280; font-size: 12px; border-top: 1px solid #1f2937; padding-top: 16px; }
    </style>
</head>
<body>
    <h1>ETUI Debug — Stock Fixtures</h1>
    <p class="lead">Phase 1 parser viewer. Click any file to run it through <code>MenuFile::parse()</code> and inspect the AST.</p>

    <div class="grid">
        @foreach ($files as $mod => $names)
            <div class="col">
                <h2>{{ $mod }} <span class="count">({{ count($names) }} files)</span></h2>
                <ul>
                    @foreach ($names as $name)
                        <li><a href="{{ route('etui-debug.show', ['mod' => $mod, 'file' => $name]) }}">{{ $name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="footer">Phase 1 status: 143 tests passing (51 unit + 92/135 golden). Read-only dev tool — Phase 4 replaces this with the proper public viewer.</div>
</body>
</html>
