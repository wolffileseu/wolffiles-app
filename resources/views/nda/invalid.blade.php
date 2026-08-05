<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Link ungueltig &middot; Wolffiles.eu</title>
<style>
body { margin:0; background:#0f1115; color:#e6e8ec; font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px; }
.card { background:#171a21; border:1px solid #2a2f3a; border-radius:10px; padding:28px; max-width:480px; }
h1 { font-size:1.25rem; margin:0 0 12px; }
p { margin:0 0 10px; }
.muted { color:#9aa3b2; font-size:.88rem; }
a { color:#c8a04a; }
</style>
</head>
<body>
<div class="card">
  <h1>@switch($reason)
      @case('used') Dieser Link wurde bereits verwendet @break
      @case('expired') Dieser Link ist abgelaufen @break
      @case('revoked') Dieser Link wurde widerrufen @break
      @case('no_template') Vorlage nicht verfuegbar @break
      @default Link ungueltig
    @endswitch</h1>

  <p>@switch($reason)
      @case('used')
        Die Vereinbarung wurde bereits unterzeichnet. Jeder Link ist genau einmal gueltig.
        @break
      @case('expired')
        Die Gueltigkeitsdauer dieses Links ist abgelaufen.
        @break
      @case('revoked')
        Dieser Einladungslink wurde vom Betreiber zurueckgezogen.
        @break
      @case('no_template')
        Zu dieser Einladung ist keine Vertragsvorlage hinterlegt. Bitte melde dich beim Betreiber.
        @break
      @default
        Dieser Link existiert nicht oder ist nicht mehr gueltig.
    @endswitch</p>

  <p class="muted">Wenn du glaubst, dass das ein Fehler ist, wende dich an
    <a href="mailto:{{ config('nda.operator_email') }}">{{ config('nda.operator_email') }}</a>.</p>
</div>
</body>
</html>
