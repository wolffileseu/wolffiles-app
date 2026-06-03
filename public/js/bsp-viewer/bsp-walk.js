/*
 * bsp-walk.js - First-Person Walk-Controller im ET-Stil für den BSP-Viewer.
 *
 * Portiert aus ETc's selbstfahrendem Controller, aber direkt in EUREM
 * Q3-Koordinatensystem (Z = oben) geschrieben - also ohne GL<->Q3-Umrechnerei.
 * Enthält einen echten AABB-Box-Trace (Treppen/Wände wie im Spiel), keinen
 * Kugel-Approx.
 *
 * Verwendung (im Glue):
 *   var walk = new BSPWalk(bspTree);            // bspTree = das Objekt aus onbsp
 *   walk.enter([camX, camY, feetZ]);            // beim Wechsel in Walk-Mode
 *   walk.step(dt, {                             // jeden Frame
 *       wishDir: [wx, wy],   // horizontale Q3-Richtung (X,Y), un-/normalisiert
 *       speed:   320,        // Wunschgeschwindigkeit u/s
 *       jump:    spacePressedEdge,  // true NUR im Frame des Tastendrucks
 *       sprint:  shiftHeld
 *   });
 *   // danach: walk.position = [x, y, feetZ]  -> in cameraPosition kopieren
 *
 * Die Sichtmatrix addiert bei euch bereits +57 Augenhöhe (-camZ - 57),
 * deshalb ist walk.position[2] die FUSS-Position (Z), passend dazu.
 */
(function (global) {
  'use strict';

  /* Q3/ET Surface-Flag für Leitern (für später). */
  var SURF_LADDER = 0x8;
  var CONTENTS_SOLID = 1;
  var CONTENTS_PLAYERCLIP = 0x10000;
  /* Spieler kollidiert mit soliden UND Playerclip-Brushes (wie ET MASK_PLAYERSOLID).
     ET-Maps legen Playerclip über holprige Geometrie -> sonst bleibt man hängen. */
  var MASK_PLAYERSOLID = CONTENTS_SOLID | CONTENTS_PLAYERCLIP;
  var TRACE_OFFSET = 0.03125;
  /* Kleiner Spalt, den der Spieler immer über dem Boden behält. Verhindert,
     dass die Box exakt bündig aufliegt (sonst meldet der Trace "allsolid" und
     das Laufen klemmt). Echtes Q3 macht dasselbe via SURFACE_CLIP_EPSILON. */
  var GROUND_EPS = 0.25;

  /* ─────────────────────────── Box-Trace ──────────────────────────────────
   * Standard-Q3-Verfahren: jede Brush-Ebene um die Box-Ausdehnung in
   * Normalenrichtung nach außen schieben -> Punkt-Trace gegen erweiterte
   * Brushes == Box-Trace gegen Original. Alles in Q3-Raum. */

  function boxOffsetForPlane(n, mins, maxs) {
    return (n[0] > 0 ? mins[0] : maxs[0]) * n[0]
         + (n[1] > 0 ? mins[1] : maxs[1]) * n[1]
         + (n[2] > 0 ? mins[2] : maxs[2]) * n[2];
  }

  function BoxTracer(bspTree) {
    this.bsp = bspTree.bsp; // { planes, nodes, leaves, brushes, brushSides, leafBrushes, surfaces }
  }

  BoxTracer.prototype.trace = function (start, end, mins, maxs) {
    var out = {
      fraction: 1.0,
      normal: [0, 0, 0],
      endPos: [end[0], end[1], end[2]],
      allsolid: false,
      startsolid: false,
      surfaceFlags: 0,
      isLadder: false
    };
    if (!this.bsp || !this.bsp.nodes) { return out; }

    // Konservative symmetrische Marge für den Baum-Abstieg (max Ausdehnung pro Achse).
    this._ext = [
      Math.max(-mins[0], maxs[0]),
      Math.max(-mins[1], maxs[1]),
      Math.max(-mins[2], maxs[2])
    ];
    this._mins = mins;
    this._maxs = maxs;

    this._node(0, 0, 1, start, end, out);

    if (out.fraction < 1.0) {
      for (var i = 0; i < 3; i++) {
        out.endPos[i] = start[i] + out.fraction * (end[i] - start[i]);
      }
    }
    return out;
  };

  BoxTracer.prototype._node = function (nodeIdx, startFrac, endFrac, start, end, out) {
    var bsp = this.bsp;

    if (nodeIdx < 0) { // Leaf
      var leaf = bsp.leaves[-(nodeIdx + 1)];
      for (var i = 0; i < leaf.leafBrushCount; i++) {
        var brush = bsp.brushes[bsp.leafBrushes[leaf.leafBrush + i]];
        var surface = bsp.surfaces[brush.shader];
        if (brush.brushSideCount > 0 && (surface.contents & MASK_PLAYERSOLID)) {
          this._brush(brush, surface, start, end, out);
        }
      }
      return;
    }

    var node  = bsp.nodes[nodeIdx];
    var plane = bsp.planes[node.plane];
    var n = plane.normal;

    // Box-Marge entlang der Knoten-Ebene (konservativ symmetrisch).
    var margin = Math.abs(n[0]) * this._ext[0]
               + Math.abs(n[1]) * this._ext[1]
               + Math.abs(n[2]) * this._ext[2];

    var startDist = n[0] * start[0] + n[1] * start[1] + n[2] * start[2] - plane.distance;
    var endDist   = n[0] * end[0]   + n[1] * end[1]   + n[2] * end[2]   - plane.distance;

    if (startDist >= margin && endDist >= margin) {
      this._node(node.children[0], startFrac, endFrac, start, end, out);
    } else if (startDist < -margin && endDist < -margin) {
      this._node(node.children[1], startFrac, endFrac, start, end, out);
    } else {
      var side, f1, f2, midFrac;
      var mid = [0, 0, 0];

      if (startDist < endDist) {
        side = 1;
        var iDist = 1 / (startDist - endDist);
        f1 = (startDist - margin + TRACE_OFFSET) * iDist;
        f2 = (startDist + margin + TRACE_OFFSET) * iDist;
      } else if (startDist > endDist) {
        side = 0;
        var iDist2 = 1 / (startDist - endDist);
        f1 = (startDist + margin + TRACE_OFFSET) * iDist2;
        f2 = (startDist - margin - TRACE_OFFSET) * iDist2;
      } else {
        side = 0; f1 = 1; f2 = 0;
      }

      if (f1 < 0) f1 = 0; else if (f1 > 1) f1 = 1;
      if (f2 < 0) f2 = 0; else if (f2 > 1) f2 = 1;

      midFrac = startFrac + (endFrac - startFrac) * f1;
      for (var a = 0; a < 3; a++) mid[a] = start[a] + f1 * (end[a] - start[a]);
      this._node(node.children[side], startFrac, midFrac, start, mid, out);

      midFrac = startFrac + (endFrac - startFrac) * f2;
      for (var b = 0; b < 3; b++) mid[b] = start[b] + f2 * (end[b] - start[b]);
      this._node(node.children[side === 0 ? 1 : 0], midFrac, endFrac, mid, end, out);
    }
  };

  BoxTracer.prototype._brush = function (brush, surface, start, end, out) {
    var startFraction = -1;
    var endFraction = 1;
    var startsOut = false;
    var endsOut = false;
    var collisionPlane = null;

    for (var i = 0; i < brush.brushSideCount; i++) {
      var brushSide = this.bsp.brushSides[brush.brushSide + i];
      var plane = this.bsp.planes[brushSide.plane];
      var n = plane.normal;

      // Box-erweiterte Ebenen-Distanz statt Kugel-radius.
      var effDist = plane.distance - boxOffsetForPlane(n, this._mins, this._maxs);

      var startDist = (n[0] * start[0] + n[1] * start[1] + n[2] * start[2]) - effDist;
      var endDist   = (n[0] * end[0]   + n[1] * end[1]   + n[2] * end[2])   - effDist;

      if (startDist > 0) startsOut = true;
      if (endDist > 0) endsOut = true;

      if (startDist > 0 && endDist > 0) { return; }
      if (startDist <= 0 && endDist <= 0) { continue; }

      if (startDist > endDist) { // tritt in den Brush ein
        var fr = (startDist - TRACE_OFFSET) / (startDist - endDist);
        if (fr > startFraction) { startFraction = fr; collisionPlane = plane; }
      } else { // verlässt den Brush
        var fr2 = (startDist + TRACE_OFFSET) / (startDist - endDist);
        if (fr2 < endFraction) endFraction = fr2;
      }
    }

    if (startsOut === false) {
      out.startsolid = true;
      if (endsOut === false) out.allsolid = true;
      return;
    }

    if (startFraction < endFraction) {
      if (startFraction > -1 && startFraction < out.fraction) {
        if (startFraction < 0) startFraction = 0;
        out.fraction = startFraction;
        out.normal = [collisionPlane.normal[0], collisionPlane.normal[1], collisionPlane.normal[2]];
        out.surfaceFlags = surface ? (surface.flags | 0) : 0;
        out.isLadder = (out.surfaceFlags & SURF_LADDER) !== 0;
      }
    }
  };

  /* ─────────────────────────── Walk-Controller ────────────────────────────*/

  function BSPWalk(bspTree) {
    this.tracer = new BoxTracer(bspTree);

    // --- ET-Einheiten (echter Maßstab) -------------------------------------
    this.gravity   = 800;   // u/s²
    this.jumpV     = 270;   // Sprung-Impuls u/s
    this.eyeHeight = 57;    // Augenhöhe über Füßen (passt zur -57 Sichtmatrix)
    this.radius    = 18;    // Spieler-Halbbreite
    this.stepUp    = 18;    // max Treppen-Stufenhöhe
    this.maxJumps  = 3;     // Dreifachsprung
    this.defaultSpeed = 320;

    // Box relativ zum Origin (Origin = Füße + 24).
    this.mins = [-this.radius, -this.radius, -24];
    this.maxs = [ this.radius,  this.radius,  32];

    // --- Zustand ------------------------------------------------------------
    this.position    = [0, 0, 0]; // FÜSSE in Q3 (Z = oben)
    this.vz          = 0;
    this.onGround    = false;
    this.groundNormal = [0, 0, 1];
    this.jumpsLeft   = this.maxJumps;
  }

  /* Beim Wechsel in den Walk-Mode: Position übernehmen und auf den Boden snappen. */
  BSPWalk.prototype.enter = function (feetPos) {
    this.position = [feetPos[0], feetPos[1], feetPos[2]];
    this.vz = 0;
    // Snap-Start 1u anheben, damit ein exakt bündiger Toggle nicht in den
    // Boundary-Fall des Brush-Trace läuft (startDist==0 == "innen").
    var origin = [this.position[0], this.position[1], this.position[2] + 24 + 1];
    var down   = [origin[0], origin[1], origin[2] - 4096];
    var tr = this.tracer.trace(origin, down, this.mins, this.maxs);
    if (tr.fraction < 1.0) {
      var hitZ = origin[2] + (down[2] - origin[2]) * tr.fraction;
      this.position[2] = hitZ - 24 + GROUND_EPS;
      this.onGround = true;
      this.groundNormal = tr.normal[2] ? tr.normal : [0, 0, 1];
    } else {
      this.onGround = false;
    }
    this.jumpsLeft = this.maxJumps;
    return this.position;
  };

  BSPWalk.prototype._trace = function (start, end) {
    return this.tracer.trace(start, end, this.mins, this.maxs);
  };

  /* Q3 PM_SlideMove (aus ETc portiert, Z = oben). origin/dest = Box-Origin. */
  BSPWalk.prototype._slideMove = function (origin, dest) {
    var OVERCLIP = 1.001, MAX_PLANES = 5, numBumps = 4;
    var pos = [origin[0], origin[1], origin[2]];
    var vel = [dest[0] - origin[0], dest[1] - origin[1], dest[2] - origin[2]];
    var planes = [];
    var time_left = 1.0;
    var i, j, d;

    if (this.onGround) {
      planes.push([this.groundNormal[0], this.groundNormal[1], this.groundNormal[2]]);
    }

    for (var bump = 0; bump < numBumps; bump++) {
      if (vel[0] === 0 && vel[1] === 0 && vel[2] === 0) break;

      var to = [pos[0] + vel[0] * time_left, pos[1] + vel[1] * time_left, pos[2] + vel[2] * time_left];
      var tr = this._trace(pos, to);

      if (tr.allsolid) {
        var nudges = [[0.125,0,0],[-0.125,0,0],[0,0.125,0],[0,-0.125,0],[0,0,0.125],[0,0,-0.125],[0,0,0.25]];
        var escaped = false;
        for (var ni = 0; ni < nudges.length; ni++) {
          var nu = [pos[0]+nudges[ni][0], pos[1]+nudges[ni][1], pos[2]+nudges[ni][2]];
          var nt = this._trace(nu, nu);
          if (!nt.allsolid && !nt.startsolid) { pos = nu; escaped = true; break; }
        }
        if (!escaped) { vel[0]=vel[1]=vel[2]=0; }
        return pos;
      }

      if (tr.fraction > 0) {
        pos[0] += (to[0]-pos[0]) * tr.fraction;
        pos[1] += (to[1]-pos[1]) * tr.fraction;
        pos[2] += (to[2]-pos[2]) * tr.fraction;
      }
      if (tr.fraction >= 1.0) break;

      time_left -= time_left * tr.fraction;
      if (planes.length < MAX_PLANES) planes.push(tr.normal);

      var clip = [0,0,0], found = false;
      for (i = 0; i < planes.length; i++) {
        var backoff = vel[0]*planes[i][0] + vel[1]*planes[i][1] + vel[2]*planes[i][2];
        if (backoff < 0) backoff *= OVERCLIP; else backoff /= OVERCLIP;
        clip[0] = vel[0] - planes[i][0]*backoff;
        clip[1] = vel[1] - planes[i][1]*backoff;
        clip[2] = vel[2] - planes[i][2]*backoff;
        var valid = true;
        for (j = 0; j < planes.length; j++) {
          if (j === i) continue;
          if (clip[0]*planes[j][0] + clip[1]*planes[j][1] + clip[2]*planes[j][2] < -0.01) { valid = false; break; }
        }
        if (valid) { vel[0]=clip[0]; vel[1]=clip[1]; vel[2]=clip[2]; found = true; break; }
      }

      if (!found) {
        if (planes.length >= 2) {
          var n1 = planes[planes.length-2], n2 = planes[planes.length-1];
          var cx = n1[1]*n2[2]-n1[2]*n2[1], cy = n1[2]*n2[0]-n1[0]*n2[2], cz = n1[0]*n2[1]-n1[1]*n2[0];
          var cl = Math.sqrt(cx*cx+cy*cy+cz*cz);
          if (cl > 1e-6) {
            cx/=cl; cy/=cl; cz/=cl;
            d = vel[0]*cx + vel[1]*cy + vel[2]*cz;
            vel[0]=cx*d; vel[1]=cy*d; vel[2]=cz*d;
          } else { vel[0]=vel[1]=vel[2]=0; break; }
        } else { vel[0]=vel[1]=vel[2]=0; break; }
      }
    }
    return pos;
  };

  /* Ein Bewegungs-Frame. dt in Sekunden. */
  BSPWalk.prototype.step = function (dt, input) {
    input = input || {};
    var origin = [this.position[0], this.position[1], this.position[2] + 24];
    var prevFeetZ = this.position[2];

    var speed = (input.speed || this.defaultSpeed) * (input.sprint ? 2 : 1);
    var wd = input.wishDir || [0, 0];
    var wl = Math.sqrt(wd[0]*wd[0] + wd[1]*wd[1]);
    var wishX = 0, wishY = 0;
    if (wl > 0) { wishX = (wd[0]/wl) * speed * dt; wishY = (wd[1]/wl) * speed * dt; }

    /* ── Horizontaler Slide-Move ── */
    if (wishX !== 0 || wishY !== 0) {
      var svx = wishX, svy = wishY, svz = 0;

      // Auf Bodenebene projizieren, damit man Steigungen folgt statt am Brush-Seam zu fallen.
      if (this.onGround) {
        var gn = this.groundNormal;
        if (gn[0] !== 0 || gn[1] !== 0 || gn[2] !== 1) {
          var OVERCLIP = 1.001;
          var backoff = (svx*gn[0] + svy*gn[1] + svz*gn[2]) * OVERCLIP;
          var px = svx - gn[0]*backoff, py = svy - gn[1]*backoff, pz = svz - gn[2]*backoff;
          var os = Math.sqrt(svx*svx + svy*svy);
          var ps = Math.sqrt(px*px + py*py + pz*pz);
          if (ps > os*1.2 && ps > 0) { var sc = (os*1.2)/ps; px*=sc; py*=sc; pz*=sc; }
          svx = px; svy = py; svz = pz;
        }
      }

      var dest = [origin[0]+svx, origin[1]+svy, origin[2]+svz];
      var res = this._slideMove(origin, dest);

      var flatDist  = (res[0]-origin[0])*(res[0]-origin[0]) + (res[1]-origin[1])*(res[1]-origin[1]);
      var totalWish = wishX*wishX + wishY*wishY;

      // Treppen-Step: wenn blockiert und am Boden, eine Stufe hoch versuchen.
      if (flatDist < totalWish*0.99 && this.onGround) {
        var sH = this.stepUp;
        var upTr = this._trace([origin[0],origin[1],origin[2]], [origin[0],origin[1],origin[2]+sH]);
        var steppedZ = origin[2] + sH*upTr.fraction;
        var sRes = this._slideMove([origin[0], origin[1], steppedZ],
                                   [origin[0]+wishX, origin[1]+wishY, steppedZ]);
        var dnTr = this._trace([sRes[0],sRes[1],sRes[2]], [sRes[0],sRes[1],sRes[2]-sH-1]);
        if (dnTr.fraction < 1.0 && dnTr.normal[2] > 0.7) {
          var stepDist = (sRes[0]-origin[0])*(sRes[0]-origin[0]) + (sRes[1]-origin[1])*(sRes[1]-origin[1]);
          if (stepDist > flatDist) {
            var finalZ = sRes[2] + (-sH-1) * dnTr.fraction;
            origin[0] = sRes[0]; origin[1] = sRes[1]; origin[2] = finalZ;
            res = null;
          }
        }
      }

      if (res) { origin[0] = res[0]; origin[1] = res[1]; if (this.onGround) origin[2] = res[2]; }
    }

    /* ── Sprung (Edge wird vom Glue geliefert) ── */
    if (this.onGround) this.jumpsLeft = this.maxJumps;
    if (input.jump && this.jumpsLeft > 0) {
      this.vz = this.jumpV;
      this.jumpsLeft--;
      this.onGround = false;
    }

    /* ── Schwerkraft + vertikaler Trace ── */
    if (!this.onGround) this.vz -= this.gravity * dt;
    var vdz = this.vz * dt;
    if (vdz !== 0 || !this.onGround) {
      var vTr = this._trace([origin[0],origin[1],origin[2]], [origin[0],origin[1],origin[2]+vdz]);
      if (vTr.fraction < 1.0) {
        origin[2] = origin[2] + vdz*vTr.fraction;
        if (vTr.normal[2] > 0.7) { origin[2] += GROUND_EPS; this.vz = 0; this.onGround = true; this.groundNormal = vTr.normal; }
        else if (vTr.normal[2] < -0.7) { this.vz = 0; }
        else {
          var vn = vTr.normal, dotv = this.vz * vn[2];
          if (dotv < 0) this.vz -= vn[2]*dotv*1.001; else this.vz = 0;
        }
      } else {
        origin[2] += vdz;
        this.onGround = false;
      }
    }

    /* ── Boden-Trace (snappen / Treppen runter) ── */
    if (this.vz <= 0) {
      var probe = this.onGround ? (this.stepUp + 4) : 4;
      var gTr = this._trace([origin[0],origin[1],origin[2]], [origin[0],origin[1],origin[2]-probe]);
      if (gTr.fraction < 1.0 && gTr.normal[2] > 0.7) {
        origin[2] = origin[2] - probe*gTr.fraction + GROUND_EPS;
        this.vz = 0; this.onGround = true; this.groundNormal = gTr.normal;
      } else if (gTr.fraction < 1.0 && gTr.normal[2] > 0.3 && this.onGround) {
        origin[2] = origin[2] - probe*gTr.fraction + GROUND_EPS;
        this.vz = 0; this.onGround = true; this.groundNormal = gTr.normal;
      } else if (this.onGround) {
        this.onGround = false;
      }
    }

    this.position[0] = origin[0];
    this.position[1] = origin[1];
    this.position[2] = origin[2] - 24;
    void prevFeetZ; // (Step-Smoothing der Sicht kommt in v2)
    return this.position;
  };

  global.BSPWalk = BSPWalk;

})(window);