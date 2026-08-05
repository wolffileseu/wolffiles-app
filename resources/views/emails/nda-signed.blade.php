@php
    $en = $nda->locale === 'en' && ! $forOperator;
@endphp
<!DOCTYPE html>
<html lang="{{ $forOperator ? 'de' : $nda->locale }}">
<head>
<meta charset="utf-8">
<style>
body { background:#f4f4f2; margin:0; padding:24px 12px; font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#1a1a1a; }
.card { max-width:640px; margin:0 auto; background:#fff; border:1px solid #ddd; border-radius:8px; padding:28px; }
h1 { font-size:1.2rem; margin:0 0 14px; }
p { margin:0 0 12px; }
dl { display:grid; grid-template-columns:auto 1fr; gap:5px 14px; font-size:.85rem; background:#f7f6f3; padding:14px 16px; border-radius:6px; }
dt { color:#666; }
dd { margin:0; word-break:break-all; }
hr { border:0; border-top:1px solid #e2e2e2; margin:22px 0; }
.doc { font-size:.82rem; }
.doc h2 { font-size:.95rem; margin-top:1.5em; border-bottom:1px solid #e2e2e2; padding-bottom:.25em; }
.doc blockquote { border-left:3px solid #c8a04a; margin:1em 0; padding:.2em 1em; background:#faf6ec; }
.doc hr { margin:1.5em 0; }
.doc ul { padding-left:1.2em; }
.muted { color:#777; font-size:.8rem; }
</style>
</head>
<body>
<div class="card">

@if ($forOperator)
    <h1>Neue NDA-Unterschrift</h1>
    <p>{{ $nda->volunteer_name }} hat die Vereinbarung fuer die Rolle <strong>{{ $nda->role_name }}</strong> unterzeichnet.</p>
    <p class="muted">Berechtigungen sind noch <strong>nicht</strong> vergeben. Verknuepfe den Vertrag im Admin-Bereich mit einem Benutzerkonto.</p>
@else
    <h1>{{ $en ? 'Your signed agreement' : 'Deine unterzeichnete Vereinbarung' }}</h1>
    <p>{{ $en
        ? 'Thank you for signing. Below is the exact wording you accepted. Please keep this email.'
        : 'Danke fuers Unterzeichnen. Unten steht der genaue Wortlaut, den du akzeptiert hast. Bitte bewahre diese E-Mail auf.' }}</p>
@endif

<dl>
    <dt>{{ $en ? 'Name' : 'Name' }}</dt><dd>{{ $nda->volunteer_name }}</dd>
    <dt>E-Mail</dt><dd>{{ $nda->volunteer_email }}</dd>
    @if ($nda->volunteer_discord)
        <dt>Discord</dt><dd>{{ $nda->volunteer_discord }}</dd>
    @endif
    <dt>{{ $en ? 'Role' : 'Rolle' }}</dt><dd>{{ $nda->role_name }}</dd>
    <dt>{{ $en ? 'Signed at' : 'Unterzeichnet am' }}</dt><dd>{{ $nda->signed_at->format('d.m.Y H:i') }}</dd>
    @if ($forOperator)
        <dt>IP</dt><dd>{{ $nda->signed_ip }}</dd>
        <dt>Browser</dt><dd>{{ $nda->signed_user_agent }}</dd>
        <dt>Geburtsdatum</dt><dd>{{ optional($nda->volunteer_birthdate)->format('d.m.Y') }}</dd>
        <dt>Land</dt><dd>{{ $nda->volunteer_country }}</dd>
    @endif
    <dt>SHA-256</dt><dd>{{ $nda->document_hash }}</dd>
</dl>

<hr>

<div class="doc">{!! \Illuminate\Support\Str::markdown($nda->rendered_body) !!}</div>

<hr>
<p class="muted">Wolffiles.eu &middot; Kevin Wahl &middot; 37A route de Luxembourg &middot; L-6450 Echternach</p>

</div>
</body>
</html>
