#!/usr/bin/env bash
# Verdrahtet bsp-walk.js in show.blade.php (4 Edits). Idempotent-sicher:
# bricht ohne Schaden ab, falls ein Anker nicht eindeutig gefunden wird.
set -u

APP="/var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app"
F="$APP/resources/views/frontend/files/show.blade.php"
TS="$(date +%Y%m%d_%H%M%S)"

[ -f "$F" ] || { echo "FEHLER: $F nicht gefunden"; exit 1; }

if [ -f "$APP/public/js/bsp-viewer/bsp-walk.js" ]; then
  echo "bsp-walk.js: vorhanden ✓"
else
  echo "WARNUNG: $APP/public/js/bsp-viewer/bsp-walk.js fehlt -> bitte hochladen, sonst lädt der Walk-Controller nicht."
fi

\cp -f "$F" "$F.bak.$TS" && echo "Backup: $F.bak.$TS"

python3 - "$F" <<'PY'
import re, sys
F = sys.argv[1]
s = open(F, encoding="utf-8").read()

def repl(s, old, new, name):
    c = s.count(old)
    assert c == 1, "Anker '%s' nicht eindeutig (gefunden: %d)" % (name, c)
    return s.replace(old, new)

# 1) Script laden
a = '<script src="/js/bsp-viewer/q3movement.js"></script>'
s = repl(s, a, a + '\n                    <script src="/js/bsp-viewer/bsp-walk.js"></script>', "script include")

# 2) onbsp: Walk-Instanz anlegen
a = 'bspViewer.mover = new q3movement(bsp);'
s = repl(s, a, a + '\n                            bspViewer.walk = new BSPWalk(bsp);'
                  '\n                            bspViewer.prevSpace = false;', "onbsp")

# 3a) Touch-Toggle (minified)
a = 'if(!bspViewer.noclip&&bspViewer.mover){bspViewer.mover.position=[bspViewer.cameraPosition[0],bspViewer.cameraPosition[1],bspViewer.cameraPosition[2]];bspViewer.mover.velocity=[0,0,0];}'
s = repl(s, a, 'if(!bspViewer.noclip&&bspViewer.walk){bspViewer.walk.enter(bspViewer.cameraPosition);}', "touch toggle")

# 3b) Tastatur-Toggle (gespaced)
a = 'if (!bspViewer.noclip && bspViewer.mover) { bspViewer.mover.position = [bspViewer.cameraPosition[0], bspViewer.cameraPosition[1], bspViewer.cameraPosition[2]]; bspViewer.mover.velocity = [0, 0, 0]; }'
s = repl(s, a, 'if (!bspViewer.noclip && bspViewer.walk) { bspViewer.walk.enter(bspViewer.cameraPosition); }', "keyboard toggle")

# 4) Walk-Branch im Render-Loop (whitespace-tolerant per Regex)
pat = re.compile(
    r"\}\s*else\s+if\s*\(\s*bspViewer\.mover\s*\)\s*\{"
    r".*?bspViewer\.mover\.move\(\s*wishDir\s*,\s*dt\s*\*\s*1000\s*\)\s*;"
    r".*?bspViewer\.cameraPosition\[2\]\s*=\s*bspViewer\.mover\.position\[2\]\s*;\s*\}",
    re.DOTALL)
newblock = (
"""} else if (bspViewer.walk) {
                            var hx = 0, hy = 0;
                            if (bspViewer.keys["w"]) { hx += fwdX; hy += fwdY; }
                            if (bspViewer.keys["s"]) { hx -= fwdX; hy -= fwdY; }
                            if (bspViewer.keys["a"]) { hx -= rightX; hy -= rightY; }
                            if (bspViewer.keys["d"]) { hx += rightX; hy += rightY; }
                            if (bspViewer.joystick.active) {
                                hx += fwdX*(-bspViewer.joystick.dy) + rightX*bspViewer.joystick.dx;
                                hy += fwdY*(-bspViewer.joystick.dy) + rightY*bspViewer.joystick.dx;
                            }
                            var spaceNow = !!bspViewer.keys[" "];
                            var jumpEdge = spaceNow && !bspViewer.prevSpace;
                            bspViewer.prevSpace = spaceNow;
                            bspViewer.walk.step(dt, { wishDir: [hx, hy], speed: 320, jump: jumpEdge, sprint: !!bspViewer.keys['shift'] });
                            bspViewer.cameraPosition[0] = bspViewer.walk.position[0];
                            bspViewer.cameraPosition[1] = bspViewer.walk.position[1];
                            bspViewer.cameraPosition[2] = bspViewer.walk.position[2];
                        }""")
s, n = pat.subn(newblock, s)
assert n == 1, "Anker 'walk branch' nicht gefunden/mehrdeutig (gefunden: %d)" % n

open(F, "w", encoding="utf-8").write(s)
print("OK: 4 Aenderungen angewendet.")
PY
RC=$?

if [ "$RC" -ne 0 ]; then
  echo "FEHLGESCHLAGEN (RC=$RC) – kein Schaden, Original unverändert. Backup steht unter $F.bak.$TS"
  exit "$RC"
fi

chown wolffiles.eu_lkiogmaiktl:psacln "$F" && echo "chown ✓"
/opt/plesk/php/8.3/bin/php "$APP/artisan" view:clear >/dev/null 2>&1 && echo "view cache geleert ✓"
echo "FERTIG. Map öffnen, Noclip aus, testen. Rollback: \\cp \"$F.bak.$TS\" \"$F\""
