/*
 * bsp-capture.js - Screenshot + Video-Aufnahme für das BSP-Viewer-Canvas.
 *
 * Eigenständig: braucht nur das <canvas>-Element, keine Engine-Internas.
 *
 * Verwendung:
 *   var cap = new BSPCapture(canvas);
 *   cap.screenshot();                         // lädt PNG herunter
 *   cap.startRecording({ fps: 60 });          // Aufnahme starten
 *   cap.stopRecording().then(blob => { ... }); // stoppen + WebM herunterladen
 *
 * WICHTIG für Screenshots: der WebGL-Kontext muss mit
 *   getContext('webgl', { preserveDrawingBuffer: true })
 * erzeugt werden, SONST ist der Buffer beim toDataURL() oft schon geleert
 * und das PNG kommt schwarz raus. Video (captureStream) braucht das NICHT.
 */
(function (global) {
  'use strict';

  function BSPCapture(canvas) {
    this.canvas  = canvas;
    this._rec    = null;
    this._chunks = [];
    this._stream = null;
  }

  /* ── Screenshot ──────────────────────────────────────────────────────────
   * Gibt die dataURL zurück. Lädt zusätzlich eine PNG-Datei herunter,
   * außer filename === false. */
  BSPCapture.prototype.screenshot = function (filename) {
    var url;
    try {
      url = this.canvas.toDataURL('image/png');
    } catch (e) {
      console.warn('[BSPCapture] Screenshot fehlgeschlagen:', e);
      return null;
    }
    if (filename !== false) {
      this._download(url, filename || ('bsp_' + this._stamp() + '.png'));
    }
    return url;
  };

  BSPCapture.prototype.isRecording = function () { return !!this._rec; };

  /* ── Video: Aufnahme starten ───────────────────────────────────────────────
   * opts: { fps=60, bitrate=12000000, mimeType=auto } */
  BSPCapture.prototype.startRecording = function (opts) {
    if (this._rec) return false;
    opts = opts || {};

    if (typeof MediaRecorder === 'undefined' || !this.canvas.captureStream) {
      console.warn('[BSPCapture] MediaRecorder/captureStream nicht unterstützt');
      return false;
    }

    var mime = opts.mimeType;
    if (!mime) {
      var cands = ['video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm'];
      for (var i = 0; i < cands.length; i++) {
        if (MediaRecorder.isTypeSupported(cands[i])) { mime = cands[i]; break; }
      }
    }
    if (!mime) { console.warn('[BSPCapture] kein WebM-Codec verfügbar'); return false; }

    this._stream = this.canvas.captureStream(opts.fps || 60);
    this._chunks = [];

    var self = this;
    this._rec = new MediaRecorder(this._stream, {
      mimeType: mime,
      videoBitsPerSecond: opts.bitrate || 12000000
    });
    this._rec.ondataavailable = function (e) {
      if (e.data && e.data.size) self._chunks.push(e.data);
    };
    this._rec.start();
    return true;
  };

  /* ── Video: Aufnahme stoppen ──────────────────────────────────────────────
   * Liefert ein Promise mit dem fertigen Blob. Lädt zusätzlich eine
   * WebM-Datei herunter, außer filename === false. */
  BSPCapture.prototype.stopRecording = function (filename) {
    var self = this;
    return new Promise(function (resolve) {
      if (!self._rec) { resolve(null); return; }
      self._rec.onstop = function () {
        var blob = new Blob(self._chunks, { type: 'video/webm' });
        self._chunks = [];
        self._rec    = null;
        if (self._stream) {
          self._stream.getTracks().forEach(function (t) { t.stop(); });
          self._stream = null;
        }
        if (filename !== false) {
          var url = URL.createObjectURL(blob);
          self._download(url, filename || ('bsp_' + self._stamp() + '.webm'));
          setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
        }
        resolve(blob);
      };
      self._rec.stop();
    });
  };

  /* ── intern ──────────────────────────────────────────────────────────────*/
  BSPCapture.prototype._download = function (url, name) {
    var a = document.createElement('a');
    a.href = url;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
  };

  BSPCapture.prototype._stamp = function () {
    var d = new Date();
    function p(n) { return (n < 10 ? '0' : '') + n; }
    return d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + '_' +
           p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds());
  };

  global.BSPCapture = BSPCapture;

})(window);
