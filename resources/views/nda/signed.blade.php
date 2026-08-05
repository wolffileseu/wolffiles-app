<!DOCTYPE html>
<html lang="{{ $nda->locale }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $nda->locale === 'en' ? 'Agreement signed' : 'Vereinbarung unterzeichnet' }} &middot; Wolffiles.eu</title>
<style>
:root { --bg:#0f1115; --card:#171a21; --line:#2a2f3a; --text:#e6e8ec; --muted:#9aa3b2; --accent:#c8a04a; --ok:#4caf7d; }
* { box-sizing:border-box; }
body { margin:0; background:var(--bg); color:var(--text); font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
.wrap { max-width:820px; margin:0 auto; padding:40px 16px 64px; }
.card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:24px; margin-bottom:20px; }
.ok { border-color:var(--ok); }
h1 { font-size:1.4rem; margin:0 0 10px; }
p { margin:0 0 10px; }
.muted { color:var(--muted); font-size:.88rem; }
dl { display:grid; grid-template-columns:auto 1fr; gap:6px 16px; font-size:.85rem; margin:16px 0 0; }
dt { color:var(--muted); }
dd { margin:0; word-break:break-all; }
.doc { background:#fbfbfa; color:#1a1a1a; border-radius:8px; padding:28px 26px; font-size:.9rem; }
.doc h2 { font-size:1.05rem; margin-top:1.6em; border-bottom:1px solid #ddd; padding-bottom:.3em; }
.doc blockquote { border-left:3px solid var(--accent); margin:1em 0; padding:.2em 1em; background:#f3efe4; }
.doc hr { border:0; border-top:1px solid #ddd; margin:2em 0; }
.doc ul { padding-left:1.3em; }
a.btn { display:inline-block; margin-top:8px; padding:10px 18px; background:var(--accent); color:#1a1400;
  border-radius:6px; text-decoration:none; font-weight:600; font-size:.9rem; }
@media print { body { background:#fff; color:#000; } .card { border:0; background:#fff; } .noprint { display:none; } }
</style>
</head>
<body>
<div class="wrap">

  <div class="card ok">
    <h1>{{ $nda->locale === 'en' ? 'Agreement signed' : 'Vereinbarung unterzeichnet' }}</h1>
    <p>{{ $nda->locale === 'en'
        ? 'Thank you. A copy has been sent to your email address.'
        : 'Danke. Eine Kopie wurde an deine E-Mail-Adresse gesendet.' }}</p>
    <p class="muted">{{ $nda->locale === 'en'
        ? 'This link is now used up and cannot be opened again.'
        : 'Dieser Link ist jetzt verbraucht und laesst sich nicht erneut oeffnen.' }}</p>

    <dl>
      <dt>{{ $nda->locale === 'en' ? 'Name' : 'Name' }}</dt><dd>{{ $nda->volunteer_name }}</dd>
      <dt>{{ $nda->locale === 'en' ? 'Role' : 'Rolle' }}</dt><dd>{{ $nda->role_name }}</dd>
      <dt>{{ $nda->locale === 'en' ? 'Signed at' : 'Unterzeichnet am' }}</dt><dd>{{ $nda->signed_at->format('d.m.Y H:i') }}</dd>
      <dt>SHA-256</dt><dd>{{ $nda->document_hash }}</dd>
    </dl>

    <p class="noprint"><a class="btn" href="#" onclick="window.print();return false;">{{ $nda->locale === 'en' ? 'Print / save as PDF' : 'Drucken / als PDF sichern' }}</a></p>
  </div>

  <div class="card">
    <div class="doc">{!! $html !!}</div>
  </div>

</div>
</body>
</html>
