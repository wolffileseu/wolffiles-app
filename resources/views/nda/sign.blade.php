@php($t = $invitation->locale === 'en')
<!DOCTYPE html>
<html lang="{{ $invitation->locale }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $t ? 'Sign Agreement' : 'Vereinbarung unterzeichnen' }} &middot; Wolffiles.eu</title>
<style>
:root { --bg:#0f1115; --card:#171a21; --line:#2a2f3a; --text:#e6e8ec; --muted:#9aa3b2; --accent:#c8a04a; --danger:#e05260; }
* { box-sizing:border-box; }
body { margin:0; background:var(--bg); color:var(--text); font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
.wrap { max-width:820px; margin:0 auto; padding:24px 16px 64px; }
h1 { font-size:1.5rem; margin:0 0 4px; }
.sub { color:var(--muted); margin:0 0 24px; font-size:.9rem; }
.card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:20px; margin-bottom:20px; }
.card h2 { font-size:1.05rem; margin:0 0 14px; letter-spacing:.02em; }
label { display:block; font-size:.85rem; color:var(--muted); margin:0 0 5px; }
input[type=text], input[type=email], input[type=date] {
  width:100%; padding:10px 12px; background:#0d0f14; border:1px solid var(--line);
  border-radius:6px; color:var(--text); font-size:1rem; }
input:focus { outline:2px solid var(--accent); outline-offset:1px; }
.field { margin-bottom:16px; }
.grid { display:grid; gap:16px; }
@media (min-width:640px) { .grid-2 { grid-template-columns:1fr 1fr; } }
.doc { background:#fbfbfa; color:#1a1a1a; border-radius:8px; padding:28px 26px; max-height:60vh; overflow-y:auto; font-size:.9rem; }
.doc h1 { font-size:1.3rem; }
.doc h2 { font-size:1.05rem; margin-top:1.6em; border-bottom:1px solid #ddd; padding-bottom:.3em; }
.doc h3 { font-size:.98rem; }
.doc code { background:#eee; padding:1px 4px; border-radius:3px; font-size:.85em; }
.doc blockquote { border-left:3px solid var(--accent); margin:1em 0; padding:.2em 1em; background:#f3efe4; }
.doc hr { border:0; border-top:1px solid #ddd; margin:2em 0; }
.doc ul { padding-left:1.3em; }
.doc a { color:#7a5c12; }
.check { display:flex; gap:10px; align-items:flex-start; margin-bottom:12px; font-size:.9rem; line-height:1.45; }
.check input { margin-top:4px; width:18px; height:18px; flex:0 0 auto; accent-color:var(--accent); }
button { width:100%; padding:14px; background:var(--accent); color:#1a1400; border:0; border-radius:7px;
  font-size:1rem; font-weight:600; cursor:pointer; }
button:hover { filter:brightness(1.08); }
.errors { background:rgba(224,82,96,.12); border:1px solid var(--danger); border-radius:8px; padding:14px 16px; margin-bottom:20px; }
.errors ul { margin:6px 0 0; padding-left:1.2em; font-size:.9rem; }
.note { font-size:.8rem; color:var(--muted); margin-top:14px; }
.scrollhint { font-size:.8rem; color:var(--muted); margin:8px 0 0; }
</style>
</head>
<body>
<div class="wrap">

  <h1>{{ $t ? 'Volunteer and Confidentiality Agreement' : 'Ehrenamts- und Verschwiegenheitsvereinbarung' }}</h1>
  <p class="sub">Wolffiles.eu &middot; {{ $invitation->role_name }}</p>

  @if ($errors->any())
    <div class="errors">
      <strong>{{ $t ? 'Please check the following:' : 'Bitte pruefe folgende Punkte:' }}</strong>
      <ul>
        @foreach ($errors->unique() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ url('/nda/' . $token) }}">
    @csrf

    <div class="card">
      <h2>{{ $t ? 'Your details' : 'Deine Angaben' }}</h2>

      <div class="grid grid-2">
        <div class="field">
          <label for="volunteer_name">{{ $t ? 'Full name' : 'Vollstaendiger Name' }} *</label>
          <input type="text" id="volunteer_name" name="volunteer_name" value="{{ old('volunteer_name') }}" required>
        </div>
        <div class="field">
          <label for="volunteer_email">{{ $t ? 'Email' : 'E-Mail' }} *</label>
          <input type="email" id="volunteer_email" name="volunteer_email" value="{{ old('volunteer_email', $invitation->recipient_email) }}" required>
        </div>
        <div class="field">
          <label for="volunteer_username">{{ $t ? 'Username on Wolffiles' : 'Benutzername auf Wolffiles' }}</label>
          <input type="text" id="volunteer_username" name="volunteer_username" value="{{ old('volunteer_username') }}">
        </div>
        <div class="field">
          <label for="volunteer_discord">Discord</label>
          <input type="text" id="volunteer_discord" name="volunteer_discord" value="{{ old('volunteer_discord') }}">
        </div>
        <div class="field">
          <label for="volunteer_birthdate">{{ $t ? 'Date of birth' : 'Geburtsdatum' }} *</label>
          <input type="date" id="volunteer_birthdate" name="volunteer_birthdate" value="{{ old('volunteer_birthdate') }}" required>
        </div>
        <div class="field">
          <label for="volunteer_country">{{ $t ? 'Country of residence' : 'Wohnsitzland' }} *</label>
          <input type="text" id="volunteer_country" name="volunteer_country" value="{{ old('volunteer_country') }}" required>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>{{ $t ? 'The agreement' : 'Die Vereinbarung' }}</h2>
      <div class="doc">{!! $html !!}</div>
      <p class="scrollhint">{{ $t ? 'Scroll inside the box to read the full text. Your details above will be inserted into the document upon signing.' : 'Im Kasten scrollen, um den vollstaendigen Text zu lesen. Deine Angaben von oben werden beim Absenden in das Dokument eingesetzt.' }}</p>
    </div>

    <div class="card">
      <h2>{{ $t ? 'Confirmation' : 'Bestaetigung' }}</h2>

      <div class="check">
        <input type="checkbox" id="c1" name="confirm_read" value="1" required>
        <label for="c1" style="color:var(--text)">{{ $t ? 'I have read and understood this Agreement in full.' : 'Ich habe diese Vereinbarung vollstaendig gelesen und verstanden.' }}</label>
      </div>
      <div class="check">
        <input type="checkbox" id="c2" name="confirm_age" value="1" required>
        <label for="c2" style="color:var(--text)">{{ $t ? 'I am at least 18 years old and have full legal capacity.' : 'Ich bin mindestens 18 Jahre alt und unbeschraenkt geschaeftsfaehig.' }}</label>
      </div>
      <div class="check">
        <input type="checkbox" id="c3" name="confirm_secrecy" value="1" required>
        <label for="c3" style="color:var(--text)">{{ $t ? 'I undertake to maintain confidentiality without time limit.' : 'Ich verpflichte mich zur zeitlich unbegrenzten Verschwiegenheit.' }}</label>
      </div>
      <div class="check">
        <input type="checkbox" id="c4" name="confirm_unpublished" value="1" required>
        <label for="c4" style="color:var(--text)">{{ $t ? 'I will remain silent about unpublished projects until official announcement.' : 'Ich schweige ueber unveroeffentlichte Projekte bis zur offiziellen Bekanntgabe.' }}</label>
      </div>
      <div class="check">
        <input type="checkbox" id="c5" name="confirm_logging" value="1" required>
        <label for="c5" style="color:var(--text)">{{ $t ? 'I am aware that all of my actions are logged, including read-only access to user data.' : 'Mir ist bekannt, dass alle meine Handlungen protokolliert werden, einschliesslich reiner Lesezugriffe auf Nutzerdaten.' }}</label>
      </div>
      <div class="check">
        <input type="checkbox" id="c6" name="confirm_penalty" value="1" required>
        <label for="c6" style="color:var(--text)">{{ $t ? 'I am aware that breaches may result in a contractual penalty and claims for damages.' : 'Mir ist bekannt, dass Verstoesse eine Vertragsstrafe und Schadensersatzforderungen nach sich ziehen koennen.' }}</label>
      </div>

      <button type="submit">{{ $t ? 'Sign electronically' : 'Elektronisch unterzeichnen' }}</button>

      <p class="note">{{ $t ? 'Upon submission, date and time, your IP address and your browser identification are recorded and stored together with the agreement as proof. You will receive a copy by email.' : 'Beim Absenden werden Datum und Uhrzeit, deine IP-Adresse und deine Browserkennung erfasst und zusammen mit der Vereinbarung als Nachweis gespeichert. Du erhaeltst eine Kopie per E-Mail.' }}</p>
    </div>
  </form>

</div>
</body>
</html>
