/* ==========================================================================
   SIAP TES (siaptes.id) — Tes Kemampuan & Potensi (100% Gratis, Tanpa Login)
   Modul:
     1. Tes Buta Warna (18 Pelat Pseudoisokromatik Orisinal)
     2. Tes IQ Gratis (30 Soal · 25 Menit · 5 Domain + Pembahasan Langsung)
     3. Minat Karier RIASEC / Holland Code (Mode Cepat 18 & Lengkap 72 Item)
     4. Game Kognitif (4 Gim Utama + 6 Latihan Asesmen Berbasis Gim)
   ========================================================================== */
(function () {
  'use strict';

  const CACHE = {
    colorblind: null,
    iq: null,
    riasec: null
  };

  const RUNTIME = {
    tab: 'hub',
    timers: [],
    intervals: [],
    keyHandler: null,
    cb: null,
    iq: null,
    riasec: null,
    game: null
  };

  const DATA_BASE = (window.POTENSI_DATA_BASE || 'assets/data').replace(/\/$/, '');
  const ASSET_VER = window.POTENSI_ASSET_VER || '20261120';

  const STORAGE_KEY = 'SIAPTES_POTENSI_RECORDS_V1';

  /* ---------- Fluid Vector SVG Icons (Unified Stroke System) ---------- */
  const ICON_PATHS = {
    eye: '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
    brain: '<path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z"/>',
    compass: '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
    gamepad: '<line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/><line x1="15" y1="12" x2="15.01" y2="12"/><line x1="18" y1="10" x2="18.01" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    chart: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
    bulb: '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
    zap: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
    target: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
    trophy: '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
    refresh: '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
    book: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    play: '<polygon points="6 3 20 12 6 21 6 3"/>',
    grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
    shuffle: '<polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/>',
    wave: '<path d="M2 12c2-4 4-4 6 0s4 4 6 0 4-4 6 0"/>',
    hash: '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    octagon: '<polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/>',
    smile: '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',
    arrows: '<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>',
    balloon: '<path d="M12 2a7 7 0 0 0-7 7c0 4.5 3.5 8 7 10 3.5-2 7-5.5 7-10a7 7 0 0 0-7-7Z"/><path d="M12 19v3"/>',
    handshake: '<path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/>',
    layers: '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
    check: '<polyline points="20 6 9 17 4 12"/>'
  };

  function svgIcon(name, size) {
    const s = size || 16;
    const p = ICON_PATHS[name] || ICON_PATHS.zap;
    return '<svg class="ui-icon" width="' + s + '" height="' + s + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
  }

  /* ---------- Utilities ---------- */
  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function $(id) {
    return document.getElementById(id);
  }

  function addTimeout(fn, ms) {
    const id = window.setTimeout(fn, ms);
    RUNTIME.timers.push(id);
    return id;
  }

  function addInterval(fn, ms) {
    const id = window.setInterval(fn, ms);
    RUNTIME.intervals.push(id);
    return id;
  }

  function setKeyHandler(fn) {
    clearKeyHandler();
    RUNTIME.keyHandler = fn;
    document.addEventListener('keydown', fn);
  }

  function clearKeyHandler() {
    if (RUNTIME.keyHandler) {
      document.removeEventListener('keydown', RUNTIME.keyHandler);
      RUNTIME.keyHandler = null;
    }
  }

  function cleanupAsync() {
    RUNTIME.timers.forEach(function (t) { window.clearTimeout(t); });
    RUNTIME.intervals.forEach(function (i) { window.clearInterval(i); });
    RUNTIME.timers = [];
    RUNTIME.intervals = [];
    clearKeyHandler();
  }

  function fmtMMSS(ms) {
    const s = Math.max(0, Math.ceil(ms / 1000));
    const m = Math.floor(s / 60);
    const r = s % 60;
    return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
  }

  function loadRecords() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (e) {
      return {};
    }
  }

  function saveRecord(key, value) {
    try {
      const all = loadRecords();
      all[key] = value;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
    } catch (e) {}
  }

  function saveBestScore(gameId, score, maxScore) {
    const all = loadRecords();
    const games = all.games || {};
    const prev = games[gameId];
    if (!prev || score > prev.score) {
      games[gameId] = { score: score, max: maxScore || null, at: new Date().toISOString() };
      all.games = games;
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(all)); } catch (e) {}
    }
  }

  function getBestScore(gameId) {
    const all = loadRecords();
    return (all.games && all.games[gameId]) ? all.games[gameId] : null;
  }

  async function fetchJson(key, fileName) {
    if (CACHE[key]) return CACHE[key];
    const res = await fetch(DATA_BASE + '/' + fileName + '?v=' + encodeURIComponent(ASSET_VER));
    if (!res.ok) throw new Error('Gagal memuat data modul (' + res.status + ').');
    const data = await res.json();
    CACHE[key] = data;
    return data;
  }

  function scrollTopSmooth() {
    const stage = $('potensiStage');
    if (stage) {
      const top = stage.getBoundingClientRect().top + window.scrollY - 96;
      if (window.scrollY > top) {
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
      }
    }
  }

  function renderLoading(label) {
    const stage = $('potensiStage');
    if (!stage) return;
    stage.innerHTML =
      '<div class="card tp-loading">' +
        '<div class="tp-spinner"></div>' +
        '<p>Menyiapkan ' + esc(label) + '...</p>' +
      '</div>';
  }

  function renderError(msg, retryFnName) {
    const stage = $('potensiStage');
    if (!stage) return;
    stage.innerHTML =
      '<div class="card tp-empty-err">' +
        '<h3>Terjadi kendala saat memuat data</h3>' +
        '<p>' + esc(msg) + '</p>' +
        '<button type="button" class="btn btn-primary" onclick="' + retryFnName + '">Coba Muat Ulang</button>' +
      '</div>';
  }

  /* ---------- Tab & URL Router ---------- */
  const VALID_TABS = ['hub', 'buta-warna', 'iq', 'minat-karier', 'game-kognitif'];

  function updateTabUI(tab) {
    document.querySelectorAll('.tp-tab-btn').forEach(function (btn) {
      const active = btn.getAttribute('data-tab') === tab;
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });
  }

  function syncUrl(tab) {
    try {
      const url = new URL(window.location.href);
      if (tab === 'hub') {
        url.searchParams.delete('tes');
      } else {
        url.searchParams.set('tes', tab);
      }
      window.history.replaceState({ tab: tab }, '', url.toString());
    } catch (e) {}
  }

  function openTab(tab, pushScroll) {
    if (VALID_TABS.indexOf(tab) === -1) tab = 'hub';
    cleanupAsync();
    RUNTIME.tab = tab;
    updateTabUI(tab);
    syncUrl(tab);

    if (tab === 'hub') renderHub();
    else if (tab === 'buta-warna') renderColorblindIntro();
    else if (tab === 'iq') renderIQIntro();
    else if (tab === 'minat-karier') renderRiasecIntro();
    else if (tab === 'game-kognitif') renderGamesHub();

    if (pushScroll) scrollTopSmooth();
  }

  /* ==========================================================================
     0. HUB RINGKASAN (4 MODUL GRATIS)
     ========================================================================== */
  function renderHub() {
    const stage = $('potensiStage');
    if (!stage) return;
    const rec = loadRecords();

    const cbStat = rec.colorblind ? ('Terakhir: ' + rec.colorblind.correct + '/' + rec.colorblind.total + ' (' + rec.colorblind.pct + '%)') : 'Belum dicoba';
    const iqStat = rec.iq ? ('Indeks terakhir: ' + rec.iq.pct + '/100 (' + rec.iq.correct + '/30)') : 'Belum dicoba';
    const riStat = rec.riasec ? ('Kode terakhir: ' + rec.riasec.code) : 'Belum dicoba';
    const gCount = rec.games ? Object.keys(rec.games).length : 0;
    const gmStat = gCount > 0 ? (gCount + '/10 game dimainkan') : '10 game siap dimainkan';

    stage.innerHTML =
      '<div class="tp-hub-grid">' +
        '<article class="tp-mod-card tp-theme-rose">' +
          '<div class="tp-mod-top">' +
            '<span class="tp-mod-icon">' + svgIcon('eye', 22) + '</span>' +
            '<span class="tp-free-pill">GRATIS · 18 PELAT</span>' +
          '</div>' +
          '<h3>Tes Buta Warna</h3>' +
          '<p>Skrining awal persepsi warna dengan 18 pelat pseudoisokromatik orisinal (Deuteranopia, Protanopia, Tritan &amp; Umum). Lengkap dengan review tiap pelat.</p>' +
          '<div class="tp-mod-meta">' +
            '<span class="ui-inline">' + svgIcon('eye', 13) + ' Visual SVG Tajam</span>' +
            '<span class="ui-inline">' + svgIcon('clock', 13) + ' ±3 Menit</span>' +
            '<span class="ui-inline">' + svgIcon('chart', 13) + ' Hasil Instan</span>' +
          '</div>' +
          '<div class="tp-mod-foot">' +
            '<span class="tp-last-rec">' + esc(cbStat) + '</span>' +
            '<button type="button" class="btn btn-primary btn-sm" onclick="TP.openTab(\'buta-warna\', true)">Mulai Tes →</button>' +
          '</div>' +
        '</article>' +

        '<article class="tp-mod-card tp-theme-indigo">' +
          '<div class="tp-mod-top">' +
            '<span class="tp-mod-icon">' + svgIcon('brain', 22) + '</span>' +
            '<span class="tp-free-pill">GRATIS · 30 SOAL</span>' +
          '</div>' +
          '<h3>Tes IQ Online</h3>' +
          '<p>30 soal orisinal tingkat Sedang–Sulit mencakup 5 domain: Figural (matriks gambar), Numerik, Verbal, Logis, dan Spasial dengan pembahasan langsung.</p>' +
          '<div class="tp-mod-meta">' +
            '<span class="ui-inline">' + svgIcon('layers', 13) + ' 5 Domain</span>' +
            '<span class="ui-inline">' + svgIcon('clock', 13) + ' 25 Menit</span>' +
            '<span class="ui-inline">' + svgIcon('bulb', 13) + ' Pembahasan Langsung</span>' +
          '</div>' +
          '<div class="tp-mod-foot">' +
            '<span class="tp-last-rec">' + esc(iqStat) + '</span>' +
            '<button type="button" class="btn btn-primary btn-sm" onclick="TP.openTab(\'iq\', true)">Mulai Tes →</button>' +
          '</div>' +
        '</article>' +

        '<article class="tp-mod-card tp-theme-emerald">' +
          '<div class="tp-mod-top">' +
            '<span class="tp-mod-icon">' + svgIcon('compass', 22) + '</span>' +
            '<span class="tp-free-pill">GRATIS · RIASEC</span>' +
          '</div>' +
          '<h3>Tes Minat Karier</h3>' +
          '<p>Pemetaan minat karier Holland Code (R-I-A-S-E-C) untuk mengetahui 3 tema minat terkuat Anda lengkap dengan arah karier &amp; jalur seleksi yang cocok.</p>' +
          '<div class="tp-mod-meta">' +
            '<span class="ui-inline">' + svgIcon('zap', 13) + ' Cepat 18 / Lengkap 72</span>' +
            '<span class="ui-inline">' + svgIcon('target', 13) + ' Top-3 Holland Code</span>' +
          '</div>' +
          '<div class="tp-mod-foot">' +
            '<span class="tp-last-rec">' + esc(riStat) + '</span>' +
            '<button type="button" class="btn btn-primary btn-sm" onclick="TP.openTab(\'minat-karier\', true)">Mulai Tes →</button>' +
          '</div>' +
        '</article>' +

        '<article class="tp-mod-card tp-theme-plum">' +
          '<div class="tp-mod-top">' +
            '<span class="tp-mod-icon">' + svgIcon('gamepad', 22) + '</span>' +
            '<span class="tp-free-pill">GRATIS · 10 GAME</span>' +
          '</div>' +
          '<h3>Game Kognitif &amp; Asesmen</h3>' +
          '<p>4 gim kognitif cepat (Kejar Angka, Ganti Aturan, Memori Grid, Sprint Pola) plus 6 latihan asesmen kerja berbasis gim (Balon Risiko, Sinyal Berhenti, dll).</p>' +
          '<div class="tp-mod-meta">' +
            '<span class="ui-inline">' + svgIcon('zap', 13) + ' Interaktif</span>' +
            '<span class="ui-inline">' + svgIcon('trophy', 13) + ' Skor Terbaik Tersimpan</span>' +
          '</div>' +
          '<div class="tp-mod-foot">' +
            '<span class="tp-last-rec">' + esc(gmStat) + '</span>' +
            '<button type="button" class="btn btn-primary btn-sm" onclick="TP.openTab(\'game-kognitif\', true)">Main Sekarang →</button>' +
          '</div>' +
        '</article>' +
      '</div>' +

      '<div class="tp-disclaimer-box">' +
        '<b>Catatan Metodologi &amp; Privasi:</b> Seluruh tes pada menu <b>Tes Kemampuan &amp; Potensi</b> gratis diakses tanpa login dan dijalankan langsung di browser Anda (riwayat skor tersimpan lokal di perangkat Anda). ' +
        'Hasil bersifat edukatif untuk latihan, skrining mandiri, dan refleksi diri — bukan diagnosis medis/klinis, bukan skor IQ klinis resmi, dan bukan keputusan seleksi instansi.' +
      '</div>';
  }

  /* ==========================================================================
     1. TES BUTA WARNA (18 PELAT PSEUDOISOKROMATIK SVG)
     ========================================================================== */
  function renderColorblindIntro() {
    const stage = $('potensiStage');
    if (!stage) return;
    stage.innerHTML =
      '<div class="card tp-intro-card tp-border-rose">' +
        '<div class="tp-intro-head">' +
          '<div>' +
            '<span class="tp-badge-rose">GRATIS · SKRINING VISUAL PERSEPSI WARNA</span>' +
            '<h2>Tes Buta Warna Online (Pelat Pseudoisokromatik)</h2>' +
            '<p>Latihan skrining persepsi warna menggunakan 18 pelat titik warna orisinal untuk mengenali angka tersembunyi pada spektrum merah–hijau dan biru–kuning.</p>' +
          '</div>' +
        '</div>' +
        '<div class="tp-kpi-row">' +
          '<div class="tp-kpi"><b>18 Pelat</b><span>Stimulus SVG orisinal</span></div>' +
          '<div class="tp-kpi"><b>±3 Menit</b><span>Tanpa batas waktu ketat</span></div>' +
          '<div class="tp-kpi"><b>4 Kategori</b><span>Deuteran, Protan, Tritan, Umum</span></div>' +
          '<div class="tp-kpi"><b>Instan</b><span>Review tiap pelat selesai tes</span></div>' +
        '</div>' +
        '<div class="tp-tips-box">' +
          '<b>Tips sebelum memulai:</b> Atur kecerahan layar ke tingkat normal, nonaktifkan sementara fitur <i>Night Shift / Eye Comfort / Blue Light Filter</i> agar warna pelat tampil akurat, dan amati pelat dari jarak baca wajar (±40–50 cm).' +
        '</div>' +
        '<div class="tp-btn-row">' +
          '<button type="button" class="btn btn-primary" onclick="TP.startColorblind(18)">' + svgIcon('eye', 15) + ' Mulai Skrining Lengkap (18 Pelat) →</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.startColorblind(6)">' + svgIcon('zap', 15) + ' Mulai Skrining Cepat (6 Pelat Utama)</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.openTab(\'hub\', false)">← Semua Tes Gratis</button>' +
        '</div>' +
        '<div class="tp-disclaimer-box" style="margin-top:1.1rem;">' +
          '<b>Disclaimer Medis:</b> Pelat ini adalah stimulus latihan pseudoisokromatik orisinal, bukan reproduksi pelat Ishihara resmi dan bukan pengganti pemeriksaan dokter mata / optometris.' +
        '</div>' +
      '</div>';
  }

  async function startColorblind(count) {
    cleanupAsync();
    renderLoading('Tes Buta Warna');
    try {
      const allPlates = await fetchJson('colorblind', 'tes_colorblind.json');
      const plates = allPlates.slice(0, Math.min(count || 18, allPlates.length));
      RUNTIME.cb = {
        plates: plates,
        index: 0,
        answers: new Array(plates.length).fill(null),
        startedAt: Date.now()
      };
      renderColorblindQuestion();
      scrollTopSmooth();
    } catch (e) {
      renderError(e.message, 'TP.startColorblind(' + (count || 18) + ')');
    }
  }

  function renderColorblindQuestion() {
    const st = RUNTIME.cb;
    const stage = $('potensiStage');
    if (!st || !stage) return;

    const p = st.plates[st.index];
    const total = st.plates.length;
    const chosen = st.answers[st.index];
    const answered = chosen !== null;
    const pct = Math.round(((st.index + (answered ? 1 : 0)) / total) * 100);

    const optsHtml = p.options.map(function (opt, idx) {
      let cls = 'tp-cb-opt';
      if (answered) {
        if (idx === p.answer) cls += ' is-correct';
        else if (idx === chosen) cls += ' is-wrong';
      }
      return (
        '<button type="button" class="' + cls + '" ' + (answered ? 'disabled' : '') + ' onclick="TP.answerColorblind(' + idx + ')">' +
          '<span class="tp-opt-letter">' + String.fromCharCode(65 + idx) + '</span>' +
          '<span class="tp-opt-text">' + esc(opt) + '</span>' +
        '</button>'
      );
    }).join('');

    let feedbackHtml = '';
    if (answered) {
      const ok = chosen === p.answer;
      feedbackHtml =
        '<div class="tp-feedback ' + (ok ? 'ok' : 'bad') + '">' +
          '<b>' + (ok ? '✓ Jawaban tepat!' : '✕ Jawaban berbeda dari kunci pelat') + '</b>' +
          '<p>' + esc(p.explanation) + '</p>' +
        '</div>';
    }

    stage.innerHTML =
      '<div class="card tp-runner-card">' +
        '<div class="tp-runner-head">' +
          '<div class="tp-runner-chips">' +
            '<span class="tp-badge-rose">TES BUTA WARNA</span>' +
            '<span class="tp-chip">' + esc(p.type) + '</span>' +
          '</div>' +
          '<span class="tp-step-counter">Pelat <b>' + (st.index + 1) + '</b> / ' + total + '</span>' +
        '</div>' +
        '<div class="tp-progress"><div class="tp-progress-fill rose" style="width:' + pct + '%"></div></div>' +

        '<div class="tp-cb-layout">' +
          '<div class="tp-cb-plate-col">' +
            '<div class="tp-cb-plate-frame">' +
              '<img src="' + esc(p.image) + '?v=' + encodeURIComponent(ASSET_VER) + '" alt="Pelat Tes Buta Warna ' + p.plate_no + '" width="260" height="260">' +
            '</div>' +
            '<div class="tp-cb-plate-cap">' + esc(p.caption) + '</div>' +
          '</div>' +
          '<div class="tp-cb-opts-col">' +
            '<h3 class="tp-q-prompt">' + esc(p.prompt) + '</h3>' +
            '<div class="tp-cb-opts">' + optsHtml + '</div>' +
            feedbackHtml +
            '<div class="tp-runner-actions">' +
              '<button type="button" class="btn btn-outline btn-sm" onclick="TP.renderColorblindIntro()">Keluar</button>' +
              '<button type="button" class="btn btn-primary" ' + (answered ? '' : 'disabled') + ' onclick="TP.nextColorblind()">' +
                (st.index === total - 1 ? 'Lihat Hasil Skrining →' : 'Pelat Berikutnya →') +
              '</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';

    setKeyHandler(function (e) {
      if (!RUNTIME.cb) return;
      if (e.key === 'Enter' && RUNTIME.cb.answers[RUNTIME.cb.index] !== null) {
        e.preventDefault();
        nextColorblind();
      }
    });
  }

  function answerColorblind(optIdx) {
    const st = RUNTIME.cb;
    if (!st || st.answers[st.index] !== null) return;
    st.answers[st.index] = optIdx;
    renderColorblindQuestion();
  }

  function nextColorblind() {
    const st = RUNTIME.cb;
    if (!st || st.answers[st.index] === null) return;
    if (st.index < st.plates.length - 1) {
      st.index++;
      renderColorblindQuestion();
    } else {
      finishColorblind();
    }
  }

  function finishColorblind() {
    cleanupAsync();
    const st = RUNTIME.cb;
    const stage = $('potensiStage');
    if (!st || !stage) return;

    const total = st.plates.length;
    let correct = 0;
    const byType = {};
    st.plates.forEach(function (p, i) {
      if (!byType[p.type]) byType[p.type] = { c: 0, t: 0 };
      byType[p.type].t++;
      if (st.answers[i] === p.answer) {
        correct++;
        byType[p.type].c++;
      }
    });
    const pct = Math.round((correct / total) * 100);
    const sec = Math.max(1, Math.round((Date.now() - st.startedAt) / 1000));
    saveRecord('colorblind', { correct: correct, total: total, pct: pct, at: new Date().toISOString() });

    let statusTitle = 'Persepsi Warna Sangat Baik (Normal pada Skrining Latihan)';
    let statusClass = 'ok';
    let statusDesc = 'Anda berhasil membaca hampir seluruh atau seluruh pelat warna dengan akurat. Kondisi ini menunjukkan persepsi warna yang baik pada skrining layar latihan.';
    if (pct < 72) {
      statusTitle = 'Perlu Pemeriksaan Lanjutan ke Profesional';
      statusClass = 'warn';
      statusDesc = 'Terdapat cukup banyak pelat yang berbeda dari kunci. Pastikan filter warna layar tidak aktif, atau jadwalkan tes Ishihara resmi dengan dokter mata/optometris untuk kepastian medis.';
    } else if (pct < 90) {
      statusTitle = 'Cukup Baik — Ada Beberapa Pelat Terlewat';
      statusClass = 'mid';
      statusDesc = 'Sebagian besar pelat terbaca tepat, namun beberapa pelat kontras halus terlewat. Ulangi di ruangan terang tanpa filter layar, atau konsultasikan bila diperlukan untuk syarat seleksi.';
    }

    const typeBars = Object.keys(byType).map(function (tName) {
      const d = byType[tName];
      const tp = Math.round((d.c / d.t) * 100);
      return (
        '<div class="tp-domain-item">' +
          '<div class="tp-domain-top"><span>' + esc(tName) + '</span><b>' + d.c + '/' + d.t + ' (' + tp + '%)</b></div>' +
          '<div class="tp-bar"><i style="width:' + tp + '%"></i></div>' +
        '</div>'
      );
    }).join('');

    const reviewCards = st.plates.map(function (p, i) {
      const ans = st.answers[i];
      const ok = ans === p.answer;
      const ansText = (ans !== null && p.options[ans] !== undefined) ? p.options[ans] : '—';
      return (
        '<div class="tp-cb-rev-item ' + (ok ? 'ok' : 'bad') + '">' +
          '<img src="' + esc(p.image) + '?v=' + encodeURIComponent(ASSET_VER) + '" alt="Pelat ' + p.plate_no + '" loading="lazy">' +
          '<div class="tp-cb-rev-info">' +
            '<b>Pelat #' + p.plate_no + ' · ' + (ok ? '✓ Tepat' : '✕ Berbeda') + '</b>' +
            '<small>' + esc(p.type) + '</small>' +
            '<div>Jawaban Anda: <b>' + esc(ansText) + '</b></div>' +
            '<div>Kunci Pelat: <b>' + esc(p.answer_label) + '</b></div>' +
          '</div>' +
        '</div>'
      );
    }).join('');

    stage.innerHTML =
      '<div class="card tp-result-card">' +
        '<div class="tp-score-hero">' +
          '<div class="tp-ring ' + statusClass + '" style="--p:' + pct + '">' +
            '<div class="tp-ring-inner"><b>' + pct + '%</b><span>' + correct + '/' + total + ' Pelat</span></div>' +
          '</div>' +
          '<div class="tp-score-summary">' +
            '<span class="tp-badge-rose">HASIL SKRINING BUTA WARNA</span>' +
            '<h2>' + esc(statusTitle) + '</h2>' +
            '<p>' + esc(statusDesc) + '</p>' +
            '<div class="tp-score-facts">' +
              '<span class="ui-inline">' + svgIcon('check', 14) + ' Benar: <b>' + correct + '/' + total + '</b></span>' +
              '<span class="ui-inline">' + svgIcon('clock', 14) + ' Waktu: <b>' + Math.floor(sec / 60) + 'm ' + (sec % 60) + 'd</b></span>' +
            '</div>' +
          '</div>' +
        '</div>' +

        '<div class="tp-domains-grid">' + typeBars + '</div>' +

        '<div class="tp-btn-row" style="margin-top:1.25rem;">' +
          '<button type="button" class="btn btn-primary" onclick="TP.startColorblind(' + total + ')">' + svgIcon('refresh', 15) + ' Ulangi Tes Buta Warna</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.toggleEl(\'cbReviewBox\')">' + svgIcon('eye', 15) + ' Review Semua Pelat</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.openTab(\'iq\', true)">' + svgIcon('brain', 15) + ' Lanjut ke Tes IQ →</button>' +
        '</div>' +

        '<div class="tp-disclaimer-box" style="margin-top:1.1rem;">' +
          '<b>Catatan Medis:</b> Hasil layar sangat dipengaruhi kalibrasi monitor dan pencahayaan. Untuk persyaratan tes kesehatan CPNS, Sekolah Kedinasan, TNI/Polri, atau BUMN, tetap lakukan pemeriksaan langsung dengan dokter mata.' +
        '</div>' +
      '</div>' +
      '<div class="card tp-review-card hidden" id="cbReviewBox">' +
        '<h3>Review ' + total + ' Pelat Warna</h3>' +
        '<div class="tp-cb-rev-grid">' + reviewCards + '</div>' +
      '</div>';
    scrollTopSmooth();
  }

  /* ==========================================================================
     2. TES IQ GRATIS (30 SOAL · 25 MENIT · 5 DOMAIN + PEMBAHASAN LANGSUNG)
     ========================================================================== */
  function renderIQIntro() {
    const stage = $('potensiStage');
    if (!stage) return;
    stage.innerHTML =
      '<div class="card tp-intro-card tp-border-indigo">' +
        '<div class="tp-intro-head">' +
          '<div>' +
            '<span class="tp-badge-indigo">GRATIS · 30 SOAL · 5 DOMAIN PENALARAN</span>' +
            '<h2>Tes IQ Online Gratis (Mode Latihan &amp; Pembahasan Langsung)</h2>' +
            '<p>Uji ketajaman penalaran Anda melalui 30 soal orisinal berdurasi 25 menit dengan tingkat kesulitan Sedang hingga Sulit. Begitu menjawab, sistem langsung menampilkan koreksi dan pembahasan ringkas.</p>' +
          '</div>' +
        '</div>' +
        '<div class="tp-kpi-row">' +
          '<div class="tp-kpi"><b>30 Soal</b><span>Bank soal orisinal</span></div>' +
          '<div class="tp-kpi"><b>25 Menit</b><span>Timer hitung mundur</span></div>' +
          '<div class="tp-kpi"><b>5 Domain</b><span>Figural, Numerik, Verbal, Logis, Spasial</span></div>' +
          '<div class="tp-kpi"><b>Langsung</b><span>Pembahasan tiap soal</span></div>' +
        '</div>' +
        '<div class="tp-domain-pills">' +
          '<span><b>Figural (8):</b> Matriks &amp; pola gambar SVG</span>' +
          '<span><b>Numerik (6):</b> Deret &amp; problem solving</span>' +
          '<span><b>Verbal (6):</b> Analogi &amp; relasi makna</span>' +
          '<span><b>Logis (5):</b> Silogisme &amp; implikasi</span>' +
          '<span><b>Spasial (5):</b> Rotasi &amp; transformasi bentuk</span>' +
        '</div>' +
        '<div class="tp-btn-row">' +
          '<button type="button" class="btn btn-primary" onclick="TP.startIQ()">' + svgIcon('brain', 15) + ' Mulai Tes IQ Gratis (30 Soal) →</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.openTab(\'hub\', false)">← Semua Tes Gratis</button>' +
        '</div>' +
        '<div class="tp-disclaimer-box" style="margin-top:1.1rem;">' +
          '<b>Catatan Validitas:</b> Hasil ditampilkan sebagai <b>Indeks Latihan / 100</b> per domain penalaran, bukan skor IQ klinis (WAIS/Stanford-Binet) dan bukan sertifikat kualifikasi Mensa.' +
        '</div>' +
      '</div>';
  }

  async function startIQ() {
    cleanupAsync();
    renderLoading('Tes IQ Gratis (30 Soal)');
    try {
      const bank = await fetchJson('iq', 'tes_iq.json');
      const now = Date.now();
      RUNTIME.iq = {
        bank: bank,
        index: 0,
        answers: {},
        startedAt: now,
        deadline: now + 25 * 60 * 1000,
        finished: false
      };
      addInterval(tickIQ, 500);
      renderIQQuestion();
      scrollTopSmooth();
    } catch (e) {
      renderError(e.message, 'TP.startIQ()');
    }
  }

  function tickIQ() {
    const st = RUNTIME.iq;
    if (!st || st.finished) return;
    const rem = st.deadline - Date.now();
    const el = $('tpIqTimer');
    if (el) {
      el.textContent = fmtMMSS(rem);
      el.classList.toggle('urgent', rem <= 3 * 60 * 1000);
    }
    if (rem <= 0) finishIQ(true);
  }

  function renderIQQuestion() {
    const st = RUNTIME.iq;
    const stage = $('potensiStage');
    if (!st || !stage) return;

    const q = st.bank[st.index];
    const chosen = st.answers[q.id];
    const answered = chosen !== undefined;
    const isVisualOpts = Array.isArray(q.options_html) && q.options_html.length > 0;
    const doneCount = Object.keys(st.answers).length;
    const pct = Math.round((doneCount / st.bank.length) * 100);

    const optsHtml = q.options.map(function (optText, k) {
      let cls = 'tp-iq-opt' + (isVisualOpts ? ' is-visual' : '');
      if (answered) {
        if (k === q.answer) cls += ' is-correct';
        else if (k === chosen) cls += ' is-wrong';
      }
      const inner = (isVisualOpts && q.options_html[k])
        ? '<div class="tp-iq-opt-svg">' + q.options_html[k] + '</div><small>' + esc(optText) + '</small>'
        : '<span class="tp-opt-text">' + esc(optText) + '</span>';
      return (
        '<button type="button" class="' + cls + '" ' + (answered ? 'disabled' : '') + ' onclick="TP.answerIQ(' + k + ')">' +
          '<span class="tp-opt-letter">' + String.fromCharCode(65 + k) + '</span>' +
          '<div class="tp-iq-opt-body">' + inner + '</div>' +
        '</button>'
      );
    }).join('');

    let fbHtml = '';
    if (answered) {
      const ok = chosen === q.answer;
      fbHtml =
        '<div class="tp-feedback ' + (ok ? 'ok' : 'bad') + '">' +
          '<b>' + (ok ? '✓ Jawaban Benar!' : '✕ Jawaban Belum Tepat') + '</b>' +
          '<p>Kunci: <b>' + String.fromCharCode(65 + q.answer) + '. ' + esc(q.options[q.answer]) + '</b> — ' +
          esc(q.explanation || 'Perhatikan aturan pola pada stimulus dan terapkan secara konsisten.') + '</p>' +
        '</div>';
    }

    const navHtml = st.bank.map(function (item, idx) {
      const ans = st.answers[item.id];
      let cls = 'tp-nav-num';
      if (idx === st.index) cls += ' is-cur';
      if (ans !== undefined) cls += (ans === item.answer ? ' is-ok' : ' is-bad');
      return '<span class="' + cls + '">' + (idx + 1) + '</span>';
    }).join('');

    stage.innerHTML =
      '<div class="tp-iq-layout">' +
        '<div class="card tp-runner-card">' +
          '<div class="tp-runner-head">' +
            '<div class="tp-runner-chips">' +
              '<span class="tp-badge-indigo">TES IQ GRATIS</span>' +
              '<span class="tp-chip">' + esc(q.domain) + '</span>' +
              '<span class="tp-chip">' + esc(q.difficulty) + '</span>' +
            '</div>' +
            '<div class="tp-timer-pill" id="tpIqTimer">' + fmtMMSS(st.deadline - Date.now()) + '</div>' +
          '</div>' +
          '<div class="tp-progress"><div class="tp-progress-fill indigo" style="width:' + pct + '%"></div></div>' +

          '<div class="tp-iq-stem">' +
            '<div class="tp-q-num">Soal ' + (st.index + 1) + ' dari ' + st.bank.length + '</div>' +
            (q.prompt_html
              ? '<div class="tp-iq-visual-wrap">' + q.prompt_html + '</div><p class="tp-iq-caption">' + esc(q.prompt) + '</p>'
              : '<h3 class="tp-q-prompt">' + esc(q.prompt) + '</h3>') +
          '</div>' +

          '<div class="tp-iq-opts ' + (isVisualOpts ? 'visual-grid' : '') + '">' + optsHtml + '</div>' +
          fbHtml +

          '<div class="tp-runner-actions">' +
            '<button type="button" class="btn btn-outline btn-sm" onclick="TP.confirmExitIQ()">Keluar</button>' +
            '<button type="button" class="btn btn-primary" ' + (answered ? '' : 'disabled') + ' onclick="TP.nextIQ()">' +
              (st.index === st.bank.length - 1 ? 'Lihat Hasil &amp; Analisis →' : 'Soal Berikutnya →') +
            '</button>' +
          '</div>' +
        '</div>' +

        '<aside class="card tp-iq-side">' +
          '<h4>Progres 30 Soal</h4>' +
          '<div class="tp-nav-grid">' + navHtml + '</div>' +
          '<div class="tp-nav-legend">' +
            '<span><i class="dot ok"></i>Benar</span>' +
            '<span><i class="dot bad"></i>Salah</span>' +
            '<span><i class="dot cur"></i>Aktif</span>' +
          '</div>' +
          '<p class="tp-side-hint">Mode <b>learning test</b>: jawaban yang sudah dikoreksi terkunci otomatis agar latihan konsisten.</p>' +
        '</aside>' +
      '</div>';

    tickIQ();
    setKeyHandler(function (e) {
      if (!RUNTIME.iq || RUNTIME.iq.finished) return;
      const curQ = RUNTIME.iq.bank[RUNTIME.iq.index];
      if (e.key === 'Enter' && RUNTIME.iq.answers[curQ.id] !== undefined) {
        e.preventDefault();
        nextIQ();
      }
    });
  }

  function confirmExitIQ() {
    if (RUNTIME.iq && Object.keys(RUNTIME.iq.answers).length > 0 && !RUNTIME.iq.finished) {
      if (!window.confirm('Keluar dari sesi Tes IQ sekarang? Progres sesi ini akan direset.')) return;
    }
    cleanupAsync();
    RUNTIME.iq = null;
    renderIQIntro();
  }

  function answerIQ(optIdx) {
    const st = RUNTIME.iq;
    if (!st || st.finished) return;
    const q = st.bank[st.index];
    if (st.answers[q.id] !== undefined) return;
    st.answers[q.id] = optIdx;
    renderIQQuestion();
  }

  function nextIQ() {
    const st = RUNTIME.iq;
    if (!st || st.finished) return;
    const q = st.bank[st.index];
    if (st.answers[q.id] === undefined) return;
    if (st.index < st.bank.length - 1) {
      st.index++;
      renderIQQuestion();
      scrollTopSmooth();
    } else {
      finishIQ(false);
    }
  }

  function iqBand(pct) {
    if (pct >= 90) return { label: 'Sangat Kuat', cls: 'ok' };
    if (pct >= 80) return { label: 'Kuat', cls: 'ok' };
    if (pct >= 70) return { label: 'Baik', cls: 'ok' };
    if (pct >= 60) return { label: 'Cukup Baik', cls: 'mid' };
    if (pct >= 50) return { label: 'Berkembang', cls: 'mid' };
    return { label: 'Perlu Latihan Lebih Lanjut', cls: 'warn' };
  }

  function finishIQ(autoTimeout) {
    const st = RUNTIME.iq;
    const stage = $('potensiStage');
    if (!st || st.finished || !stage) return;
    st.finished = true;
    cleanupAsync();

    const elapsed = Math.min(25 * 60, Math.max(1, Math.round((Date.now() - st.startedAt) / 1000)));
    let correct = 0;
    let answered = 0;
    const domMap = {};

    st.bank.forEach(function (q) {
      if (!domMap[q.domain]) domMap[q.domain] = { c: 0, t: 0 };
      domMap[q.domain].t++;
      const a = st.answers[q.id];
      if (a !== undefined) answered++;
      if (a === q.answer) {
        correct++;
        domMap[q.domain].c++;
      }
    });

    const pct = Math.round((correct / st.bank.length) * 100);
    const band = iqBand(pct);
    const rows = Object.keys(domMap).map(function (name) {
      const v = domMap[name];
      return { name: name, c: v.c, t: v.t, pct: Math.round((v.c / v.t) * 100) };
    }).sort(function (a, b) { return b.pct - a.pct; });

    const strongest = rows[0];
    const weakest = rows[rows.length - 1];

    saveRecord('iq', { correct: correct, total: st.bank.length, pct: pct, band: band.label, at: new Date().toISOString() });

    const domainBars = rows.map(function (d) {
      return (
        '<div class="tp-domain-item">' +
          '<div class="tp-domain-top"><span>' + esc(d.name) + '</span><b>' + d.pct + '% (' + d.c + '/' + d.t + ')</b></div>' +
          '<div class="tp-bar"><i style="width:' + d.pct + '%"></i></div>' +
        '</div>'
      );
    }).join('');

    const reviewList = st.bank.map(function (q, i) {
      const a = st.answers[q.id];
      const ok = a === q.answer;
      return (
        '<div class="tp-rev-row ' + (ok ? 'ok' : 'bad') + '">' +
          '<div class="tp-rev-head">' +
            '<b>#' + (i + 1) + ' · ' + esc(q.domain) + ' (' + esc(q.difficulty) + ')</b>' +
            '<span class="tp-rev-tag ' + (ok ? 'ok' : 'bad') + '">' + (ok ? '✓ Benar' : '✕ Belum Tepat') + '</span>' +
          '</div>' +
          '<div class="tp-rev-q">' + esc(q.prompt) + '</div>' +
          '<div class="tp-rev-meta">' +
            'Jawaban Anda: <b>' + (a === undefined ? 'Tidak dijawab' : String.fromCharCode(65 + a) + '. ' + esc(q.options[a])) + '</b> · ' +
            'Kunci: <b>' + String.fromCharCode(65 + q.answer) + '. ' + esc(q.options[q.answer]) + '</b>' +
          '</div>' +
          '<p class="tp-rev-exp">' + esc(q.explanation || '') + '</p>' +
        '</div>'
      );
    }).join('');

    stage.innerHTML =
      '<div class="card tp-result-card">' +
        (autoTimeout ? '<div class="tp-tips-box" style="margin-bottom:1rem;"><b>Waktu 25 menit telah habis.</b> Seluruh jawaban yang sudah diisi otomatis dikalkulasi di bawah ini.</div>' : '') +
        '<div class="tp-score-hero">' +
          '<div class="tp-ring ' + band.cls + '" style="--p:' + pct + '">' +
            '<div class="tp-ring-inner"><b>' + pct + '</b><span>INDEKS / 100</span></div>' +
          '</div>' +
          '<div class="tp-score-summary">' +
            '<span class="tp-badge-indigo">HASIL TES IQ GRATIS</span>' +
            '<h2>' + esc(band.label) + '</h2>' +
            '<p>Kekuatan relatif tertinggi Anda di domain <b>' + esc(strongest.name) + ' (' + strongest.pct + '%)</b>. Area prioritas latihan berikutnya: <b>' + esc(weakest.name) + ' (' + weakest.pct + '%)</b>.</p>' +
            '<div class="tp-score-facts">' +
              '<span class="ui-inline">' + svgIcon('check', 14) + ' Benar: <b>' + correct + '/' + st.bank.length + '</b></span>' +
              '<span class="ui-inline">' + svgIcon('book', 14) + ' Terjawab: <b>' + answered + '/' + st.bank.length + '</b></span>' +
              '<span class="ui-inline">' + svgIcon('clock', 14) + ' Waktu: <b>' + Math.floor(elapsed / 60) + 'm ' + (elapsed % 60) + 'd</b></span>' +
            '</div>' +
          '</div>' +
        '</div>' +

        '<div class="tp-domains-grid">' + domainBars + '</div>' +

        '<div class="tp-btn-row" style="margin-top:1.25rem;">' +
          '<button type="button" class="btn btn-primary" onclick="TP.toggleEl(\'iqReviewBox\')">' + svgIcon('book', 15) + ' Lihat Review &amp; Pembahasan 30 Soal</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.startIQ()">' + svgIcon('refresh', 15) + ' Ulangi Tes IQ</button>' +
          '<a href="tryout.php?track=psikotes" class="btn btn-outline">' + svgIcon('play', 15) + ' Lanjut Tryout Psikotes &amp; TIU →</a>' +
        '</div>' +

        '<div class="tp-disclaimer-box" style="margin-top:1.1rem;">' +
          '<b>Catatan:</b> Skor ini merupakan indeks latihan internal (bukan IQ klinis terstandarisasi populasi dan bukan dokumen psikometri resmi).' +
        '</div>' +
      '</div>' +
      '<div class="card tp-review-card hidden" id="iqReviewBox">' +
        '<h3>Review &amp; Pembahasan Lengkap (30 Soal)</h3>' +
        '<div class="tp-rev-list">' + reviewList + '</div>' +
      '</div>';
    scrollTopSmooth();
  }

  /* ==========================================================================
     3. TES MINAT KARIER (RIASEC / HOLLAND CODE)
     ========================================================================== */
  function renderRiasecIntro() {
    const stage = $('potensiStage');
    if (!stage) return;
    stage.innerHTML =
      '<div class="card tp-intro-card tp-border-emerald">' +
        '<div class="tp-intro-head">' +
          '<div>' +
            '<span class="tp-badge-emerald">GRATIS · PEMETAAN HOLLAND CODE (R-I-A-S-E-C)</span>' +
            '<h2>Tes Minat Karier RIASEC</h2>' +
            '<p>Kenali 3 tema minat kerja paling menonjol dalam diri Anda dari enam dimensi Holland (Realistic, Investigative, Artistic, Social, Enterprising, Conventional) beserta rekomendasi peran &amp; jalur seleksi Indonesia.</p>' +
          '</div>' +
        '</div>' +
        '<div class="tp-kpi-row">' +
          '<div class="tp-kpi"><b>6 Dimensi</b><span>R · I · A · S · E · C</span></div>' +
          '<div class="tp-kpi"><b>2 Pilihan</b><span>Cepat 18 / Lengkap 72 item</span></div>' +
          '<div class="tp-kpi"><b>Top-3 Code</b><span>Kombinasi Holland Code</span></div>' +
          '<div class="tp-kpi"><b>Rekomendasi</b><span>Peran &amp; jalur seleksi</span></div>' +
        '</div>' +
        '<div class="tp-domain-pills">' +
          '<span><b>R (Realistic):</b> Praktis &amp; teknis</span>' +
          '<span><b>I (Investigative):</b> Analitis &amp; riset</span>' +
          '<span><b>A (Artistic):</b> Kreatif &amp; ekspresif</span>' +
          '<span><b>S (Social):</b> Membantu &amp; edukasi</span>' +
          '<span><b>E (Enterprising):</b> Memimpin &amp; bisnis</span>' +
          '<span><b>C (Conventional):</b> Teratur &amp; presisi</span>' +
        '</div>' +
        '<div class="tp-btn-row">' +
          '<button type="button" class="btn btn-primary" onclick="TP.startRiasec(\'quick\')">' + svgIcon('zap', 15) + ' Mulai Mode Cepat (18 Item · ±3 Menit) →</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.startRiasec(\'full\')">' + svgIcon('compass', 15) + ' Mulai Mode Lengkap (72 Item · Akurasi Lebih Dalam)</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.openTab(\'hub\', false)">← Semua Tes Gratis</button>' +
        '</div>' +
        '<div class="tp-disclaimer-box" style="margin-top:1.1rem;">' +
          '<b>Catatan Refleksi:</b> Tidak ada jawaban benar atau salah. Jawab sesuai preferensi alami Anda agar peta minat karier akurat sebagai bahan eksplorasi.' +
        '</div>' +
      '</div>';
  }

  async function startRiasec(mode) {
    cleanupAsync();
    renderLoading('Tes Minat Karier RIASEC');
    try {
      const data = await fetchJson('riasec', 'tes_riasec.json');
      const items = (mode === 'full') ? data.full : data.quick;
      RUNTIME.riasec = {
        mode: mode === 'full' ? 'full' : 'quick',
        themes: data.themes,
        items: items,
        index: 0,
        answers: new Array(items.length).fill(null)
      };
      renderRiasecQuestion();
      scrollTopSmooth();
    } catch (e) {
      renderError(e.message, "TP.startRiasec('" + (mode || 'quick') + "')");
    }
  }

  function renderRiasecQuestion() {
    const st = RUNTIME.riasec;
    const stage = $('potensiStage');
    if (!st || !stage) return;

    const item = st.items[st.index];
    const total = st.items.length;
    const sel = st.answers[st.index];
    const pct = Math.round((st.index / total) * 100);
    const scale = [
      { v: 1, label: 'Sangat Tidak Sesuai' },
      { v: 2, label: 'Tidak Sesuai' },
      { v: 3, label: 'Netral' },
      { v: 4, label: 'Sesuai' },
      { v: 5, label: 'Sangat Sesuai' }
    ];

    stage.innerHTML =
      '<div class="card tp-runner-card tp-riasec-card">' +
        '<div class="tp-runner-head">' +
          '<div class="tp-runner-chips">' +
            '<span class="tp-badge-emerald">MINAT KARIER RIASEC · ' + (st.mode === 'full' ? 'LENGKAP 72 ITEM' : 'CEPAT 18 ITEM') + '</span>' +
          '</div>' +
          '<span class="tp-step-counter">Pernyataan <b>' + (st.index + 1) + '</b> / ' + total + '</span>' +
        '</div>' +
        '<div class="tp-progress"><div class="tp-progress-fill emerald" style="width:' + pct + '%"></div></div>' +

        '<div class="tp-riasec-prompt-box">' +
          '<p class="tp-riasec-sub">Seberapa sesuai pernyataan berikut dengan diri Anda?</p>' +
          '<h3 class="tp-riasec-prompt">"' + esc(item.prompt) + '"</h3>' +
        '</div>' +

        '<div class="tp-likert-grid">' +
          scale.map(function (sc) {
            return (
              '<button type="button" class="tp-likert-btn ' + (sel === sc.v ? 'is-sel' : '') + '" onclick="TP.answerRiasec(' + sc.v + ')">' +
                '<b>' + sc.v + '</b>' +
                '<span>' + esc(sc.label) + '</span>' +
              '</button>'
            );
          }).join('') +
        '</div>' +

        '<div class="tp-runner-actions">' +
          '<button type="button" class="btn btn-outline btn-sm" onclick="TP.prevRiasec()">' +
            (st.index > 0 ? '← Sebelumnya' : '← Kembali') +
          '</button>' +
          '<span class="tp-mini-tip">Tip: bisa tekan angka <b>1–5</b> di keyboard</span>' +
        '</div>' +
      '</div>';

    setKeyHandler(function (e) {
      if (!RUNTIME.riasec) return;
      if (e.key >= '1' && e.key <= '5') {
        e.preventDefault();
        answerRiasec(Number(e.key));
      }
    });
  }

  function prevRiasec() {
    const st = RUNTIME.riasec;
    if (!st) return;
    if (st.index > 0) {
      st.index--;
      renderRiasecQuestion();
    } else {
      cleanupAsync();
      renderRiasecIntro();
    }
  }

  function answerRiasec(val) {
    const st = RUNTIME.riasec;
    if (!st) return;
    st.answers[st.index] = Number(val);
    if (st.index >= st.items.length - 1) {
      addTimeout(finishRiasec, 110);
    } else {
      st.index++;
      addTimeout(renderRiasecQuestion, 90);
    }
  }

  function finishRiasec() {
    cleanupAsync();
    const st = RUNTIME.riasec;
    const stage = $('potensiStage');
    if (!st || !stage) return;

    const bucket = { R: [], I: [], A: [], S: [], E: [], C: [] };
    st.items.forEach(function (it, i) {
      let v = Number(st.answers[i] || 3);
      if (it.reverse) v = 6 - v;
      if (bucket[it.dimension]) bucket[it.dimension].push(v);
    });

    const scores = {};
    Object.keys(bucket).forEach(function (k) {
      const arr = bucket[k];
      const avg = arr.length ? (arr.reduce(function (a, b) { return a + b; }, 0) / arr.length) : 3;
      scores[k] = Math.round(((avg - 1) / 4) * 100);
    });

    const sorted = Object.keys(scores).map(function (k) {
      return { code: k, pct: scores[k], meta: st.themes[k] };
    }).sort(function (a, b) { return b.pct - a.pct; });

    const top3 = sorted.slice(0, 3);
    const hollandCode = top3.map(function (x) { return x.code; }).join('-');
    saveRecord('riasec', { code: hollandCode, mode: st.mode, scores: scores, at: new Date().toISOString() });

    const barsHtml = sorted.map(function (d, idx) {
      return (
        '<div class="tp-domain-item ' + (idx < 3 ? 'is-top' : '') + '">' +
          '<div class="tp-domain-top">' +
            '<span><b>' + esc(d.code) + '</b> — ' + esc(d.meta.name) + ' · <small>' + esc(d.meta.tag) + '</small></span>' +
            '<b>' + d.pct + '%</b>' +
          '</div>' +
          '<div class="tp-bar emerald"><i style="width:' + d.pct + '%"></i></div>' +
        '</div>'
      );
    }).join('');

    const topCards = top3.map(function (d, rank) {
      return (
        '<div class="tp-riasec-top-card">' +
          '<div class="tp-riasec-rank">Peringkat #' + (rank + 1) + ' · ' + d.pct + '%</div>' +
          '<h4>' + esc(d.code) + ' — ' + esc(d.meta.name) + ' (' + esc(d.meta.tag) + ')</h4>' +
          '<p>' + esc(d.meta.short) + '</p>' +
          '<div class="tp-career-tags">' +
            d.meta.careers.map(function (c) { return '<span>' + esc(c) + '</span>'; }).join('') +
          '</div>' +
          '<div class="tp-track-hint">Jalur seleksi relevan: <b>' + esc(d.meta.tracks.join(' · ')) + '</b></div>' +
        '</div>'
      );
    }).join('');

    stage.innerHTML =
      '<div class="card tp-result-card">' +
        '<div class="tp-score-hero">' +
          '<div class="tp-holland-badge">' +
            '<small>HOLLAND CODE</small>' +
            '<b>' + esc(hollandCode) + '</b>' +
            '<span>' + (st.mode === 'full' ? 'Mode Lengkap 72' : 'Mode Cepat 18') + '</span>' +
          '</div>' +
          '<div class="tp-score-summary">' +
            '<span class="tp-badge-emerald">HASIL TES MINAT KARIER RIASEC</span>' +
            '<h2>Tiga Minat Utama: ' + top3.map(function (x) { return esc(x.meta.name); }).join(', ') + '</h2>' +
            '<p>Kombinasi <b>' + esc(hollandCode) + '</b> menunjukkan kecenderungan aktivitas kerja yang paling memberi Anda energi dan kepuasan. Gunakan peta ini untuk memilih formasi CPNS, BUMN, Kedinasan, atau karier profesional yang selaras.</p>' +
          '</div>' +
        '</div>' +

        '<div class="tp-domains-grid">' + barsHtml + '</div>' +

        '<h3 style="margin:1.5rem 0 0.8rem; color:var(--core-text); font-size:1.05rem;">Rekomendasi Bidang Karier &amp; Jalur Seleksi</h3>' +
        '<div class="tp-riasec-top-grid">' + topCards + '</div>' +

        '<div class="tp-btn-row" style="margin-top:1.35rem;">' +
          '<button type="button" class="btn btn-primary" onclick="TP.startRiasec(\'' + (st.mode === 'quick' ? 'full' : 'quick') + '\')">' +
            (st.mode === 'quick' ? (svgIcon('compass', 15) + ' Coba Mode Lengkap (72 Item)') : (svgIcon('zap', 15) + ' Ulangi Mode Cepat (18 Item)')) +
          '</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.startRiasec(\'' + st.mode + '\')">' + svgIcon('refresh', 15) + ' Ulangi Tes</button>' +
          '<a href="tryout.php" class="btn btn-outline">' + svgIcon('play', 15) + ' Cek Paket Tryout Sesuai Minat →</a>' +
        '</div>' +

        '<div class="tp-disclaimer-box" style="margin-top:1.1rem;">' +
          '<b>Catatan:</b> Profil RIASEC adalah alat refleksi mandiri untuk memperkaya wawasan karier, bukan penentu tunggal atau diagnosis psikometri resmi.' +
        '</div>' +
      '</div>';
    scrollTopSmooth();
  }

  /* ==========================================================================
     4. GAME KOGNITIF (4 GIM UTAMA + 6 LATIHAN ASESMEN BERBASIS GIM)
     ========================================================================== */
  const CORE_GAMES = [
    { id: 'kejar', icon: 'hash', name: 'Kejar Angka', tag: 'Kecepatan Numerik', desc: 'Sprint aritmetika 45 detik. Jawab sebanyak mungkin hitungan singkat dengan cepat & akurat.' },
    { id: 'aturan', icon: 'shuffle', name: 'Ganti Aturan', tag: 'Fleksibilitas Kognitif', desc: '20 soal klasifikasi angka dengan aturan yang berubah otomatis setiap 5 soal.' },
    { id: 'grid', icon: 'grid', name: 'Memori Grid', tag: 'Memori Kerja Visual', desc: 'Ingat posisi kotak yang menyala di papan 4×4 lalu susun ulang polanya dari ingatan.' },
    { id: 'pola', icon: 'wave', name: 'Sprint Pola', tag: 'Pengenalan Pola', desc: 'Tebak angka berikutnya pada 15 deret aritmetika, kelipatan, dan selisih bertingkat.' }
  ];

  const ASSESS_GAMES = [
    { id: 'balon', icon: 'balloon', name: 'Tantangan Balon Risiko', tag: 'Pengambilan Keputusan', desc: 'Pompa balon untuk menaikkan poin atau simpan sebelum pecah dalam 8 putaran.' },
    { id: 'percaya', icon: 'handshake', name: 'Pertukaran Kepercayaan', tag: 'Strategi Timbal Balik', desc: 'Tentukan jumlah token untuk dikirim ke mitra simulasi & amati pola pengembalian.' },
    { id: 'digit', icon: 'layers', name: 'Memori Digit', tag: 'Working Memory', desc: 'Ingat deretan angka yang semakin panjang (4–7 digit) lalu ketik kembali secara tepat.' },
    { id: 'berhenti', icon: 'octagon', name: 'Sinyal Berhenti', tag: 'Kontrol Impuls (Go/No-Go)', desc: 'Tekan tombol/Spasi saat muncul JALAN, dan tahan respons saat muncul BERHENTI.' },
    { id: 'emosi', icon: 'smile', name: 'Wajah Emosi', tag: 'Persepsi Ekspresi', desc: 'Kenali 6 ekspresi emosi dasar secara cepat dan konsisten dalam 12 putaran.' },
    { id: 'kartu', icon: 'arrows', name: 'Kartu & Panah', tag: 'Pergantian Aturan', desc: 'Pilih angka LEBIH TINGGI atau LEBIH RENDAH yang aturannya berganti tiap soal.' }
  ];

  function renderGameCard(g) {
    const best = getBestScore(g.id);
    const bestHtml = best
      ? '<span class="ui-inline">' + svgIcon('trophy', 13) + ' Terbaik: ' + best.score + (best.max ? '/' + best.max : '') + '</span>'
      : '<span>Siap dimainkan</span>';
    return (
      '<button type="button" class="tp-game-card" onclick="TP.startGame(\'' + g.id + '\')">' +
        '<div class="tp-game-card-top">' +
          '<span class="tp-game-ico">' + svgIcon(g.icon, 19) + '</span>' +
          '<span class="tp-game-tag">' + esc(g.tag) + '</span>' +
        '</div>' +
        '<b>' + esc(g.name) + '</b>' +
        '<small>' + esc(g.desc) + '</small>' +
        '<div class="tp-game-card-foot">' +
          bestHtml +
          '<span class="tp-game-go">MULAI →</span>' +
        '</div>' +
      '</button>'
    );
  }

  function renderGamesHub() {
    cleanupAsync();
    const stage = $('potensiStage');
    if (!stage) return;

    stage.innerHTML =
      '<div class="card tp-games-section">' +
        '<div class="tp-section-head">' +
          '<div>' +
            '<span class="tp-badge-plum">GRATIS · INTERAKTIF · BISA DIULANG</span>' +
            '<h2>Gim Kognitif Utama (4 Permainan)</h2>' +
            '<p>Latihan seru berdurasi 1–2 menit untuk mengasah kecepatan hitung, memori kerja visual, fleksibilitas aturan, dan pengenalan pola.</p>' +
          '</div>' +
        '</div>' +
        '<div class="tp-game-grid-4">' + CORE_GAMES.map(renderGameCard).join('') + '</div>' +
      '</div>' +

      '<div class="card tp-games-section">' +
        '<div class="tp-section-head">' +
          '<div>' +
            '<span class="tp-badge-indigo">GRATIS · LATIHAN REKRUTMEN MODERN</span>' +
            '<h2>Latihan Asesmen Berbasis Gim (6 Drill)</h2>' +
            '<p>Familiarisasi format <i>game-based assessment</i> yang kini populer di seleksi kerja perbankan, BUMN, dan perusahaan multinasional.</p>' +
          '</div>' +
        '</div>' +
        '<div class="tp-game-grid-3">' + ASSESS_GAMES.map(renderGameCard).join('') + '</div>' +
        '<div class="tp-disclaimer-box" style="margin-top:1.2rem;">' +
          '<b>Catatan Metodologi:</b> Seluruh gim adalah latihan independen untuk familiarisasi kemampuan kognitif dan asesmen berbasis gim. Bukan game resmi penyedia asesmen tertentu dan tidak menghasilkan persentil seleksi kerja.' +
        '</div>' +
      '</div>';
  }

  function renderGameShell(kicker, title, desc, innerHtml) {
    const stage = $('potensiStage');
    if (!stage) return;
    stage.innerHTML =
      '<div class="card tp-game-stage-card">' +
        '<div class="tp-runner-head">' +
          '<span class="tp-badge-plum">' + esc(kicker) + '</span>' +
          '<button type="button" class="btn btn-outline btn-sm" onclick="TP.renderGamesHub()">← Daftar Game</button>' +
        '</div>' +
        '<div class="tp-game-title-box">' +
          '<h2>' + esc(title) + '</h2>' +
          '<p>' + esc(desc) + '</p>' +
        '</div>' +
        '<div class="tp-game-arena" id="tpGameArena">' + (innerHtml || '') + '</div>' +
      '</div>';
  }

  function renderGameEnd(gameId, title, score, total, note) {
    cleanupAsync();
    saveBestScore(gameId, score, total);
    const best = getBestScore(gameId);
    const arena = $('tpGameArena');
    if (!arena) return;
    arena.innerHTML =
      '<div class="tp-game-end">' +
        '<div class="tp-game-big-score">' + score + (total ? '<span>/' + total + '</span>' : '') + '</div>' +
        '<h3>Sesi ' + esc(title) + ' Selesai!</h3>' +
        '<p class="tp-game-end-note">' + esc(note) + '</p>' +
        (best ? '<div class="tp-best-pill ui-inline">' + svgIcon('trophy', 14) + ' Rekor Terbaik Anda: <b>' + best.score + (best.max ? '/' + best.max : '') + '</b></div>' : '') +
        '<div class="tp-btn-row center">' +
          '<button type="button" class="btn btn-primary" onclick="TP.startGame(\'' + gameId + '\')">' + svgIcon('refresh', 15) + ' Main Lagi</button>' +
          '<button type="button" class="btn btn-outline" onclick="TP.renderGamesHub()">' + svgIcon('gamepad', 15) + ' Pilih Game Lain</button>' +
          '<a href="tryout.php?track=psikotes" class="btn btn-outline">' + svgIcon('play', 15) + ' Coba Tryout Psikotes →</a>' +
        '</div>' +
        '<p class="tp-mini-note">Hasil hanya untuk latihan personal &amp; pemanasan kognitif, tidak dibandingkan dengan norma populasi resmi.</p>' +
      '</div>';
  }

  function startGame(id) {
    cleanupAsync();
    scrollTopSmooth();
    if (id === 'kejar') runKejarAngka();
    else if (id === 'aturan') runGantiAturan();
    else if (id === 'grid') runMemoriGrid();
    else if (id === 'pola') runSprintPola();
    else if (id === 'balon') runBalon();
    else if (id === 'percaya') runPercaya();
    else if (id === 'digit') runDigit();
    else if (id === 'berhenti') runBerhenti();
    else if (id === 'emosi') runEmosi();
    else if (id === 'kartu') runKartu();
  }

  /* -- Game 1: Kejar Angka (45 detik) -- */
  function runKejarAngka() {
    renderGameShell('GIM KOGNITIF · KECEPATAN NUMERIK', 'Kejar Angka', 'Jawab sebanyak mungkin operasi hitung dalam 45 detik.');
    RUNTIME.game = { id: 'kejar', score: 0, tries: 0, left: 45, ans: 0 };

    function nextQ() {
      const g = RUNTIME.game;
      let a = 3 + Math.floor(Math.random() * 38);
      let b = 2 + Math.floor(Math.random() * 18);
      const plus = Math.random() < 0.5;
      if (!plus && b > a) { const tmp = a; a = b; b = tmp; }
      const ans = plus ? a + b : a - b;
      g.ans = ans;
      let opts = [ans, ans + 2, ans - 2, ans + (Math.random() < 0.5 ? 4 : -4)];
      opts = Array.from(new Set(opts));
      while (opts.length < 4) opts.push(ans + opts.length + 6);
      opts.sort(function () { return Math.random() - 0.5; });

      const arena = $('tpGameArena');
      if (!arena) return;
      arena.innerHTML =
        '<div class="tp-game-hud">' +
          '<span>Skor: <b>' + g.score + '</b></span>' +
          '<span class="tp-hud-timer ui-inline" id="tpKejarTimer">' + svgIcon('clock', 14) + ' ' + g.left + ' dtk</span>' +
        '</div>' +
        '<div class="tp-game-stimulus">' + a + ' ' + (plus ? '+' : '−') + ' ' + b + ' = ?</div>' +
        '<div class="tp-game-choices">' +
          opts.map(function (v) {
            return '<button type="button" class="tp-choice-btn" onclick="TP.gKejar(' + v + ')">' + v + '</button>';
          }).join('') +
        '</div>';
    }

    RUNTIME.game.nextQ = nextQ;
    nextQ();
    addInterval(function () {
      const g = RUNTIME.game;
      if (!g || g.id !== 'kejar') return;
      g.left--;
      const el = $('tpKejarTimer');
      if (el) el.innerHTML = svgIcon('clock', 14) + ' ' + g.left + ' dtk';
      if (g.left <= 0) {
        const acc = g.tries ? Math.round((g.score / g.tries) * 100) : 0;
        renderGameEnd('kejar', 'Kejar Angka', g.score, null, 'Anda menjawab ' + g.score + ' benar dari ' + g.tries + ' percobaan (akurasi ' + acc + '%) dalam 45 detik.');
      }
    }, 1000);
  }

  function gKejar(val) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'kejar') return;
    g.tries++;
    if (Number(val) === g.ans) g.score++;
    g.nextQ();
  }

  /* -- Game 2: Ganti Aturan (20 soal) -- */
  function runGantiAturan() {
    renderGameShell('GIM KOGNITIF · PERGANTIAN ATURAN', 'Ganti Aturan', 'Klasifikasikan setiap angka. Perhatikan aturan yang berubah tiap 5 soal!');
    RUNTIME.game = { id: 'aturan', score: 0, i: 0, ans: '' };

    function nextQ() {
      const g = RUNTIME.game;
      if (g.i >= 20) {
        return renderGameEnd('aturan', 'Ganti Aturan', g.score, 20, 'Akurasi Anda saat mempertahankan dan berpindah aturan. Amati apakah jeda melambat tepat setelah aturan berganti.');
      }
      const blok = Math.floor(g.i / 5) % 2;
      const n = 1 + Math.floor(Math.random() * 99);
      g.ans = blok === 0 ? (n % 2 === 0 ? 'YA' : 'TIDAK') : (n > 50 ? 'YA' : 'TIDAK');
      const ruleText = blok === 0 ? 'Jawab YA jika angka GENAP' : 'Jawab YA jika angka LEBIH BESAR DARI 50';

      const arena = $('tpGameArena');
      if (!arena) return;
      arena.innerHTML =
        '<div class="tp-game-hud">' +
          '<span>Soal: <b>' + (g.i + 1) + '/20</b></span>' +
          '<span>Skor: <b>' + g.score + '</b></span>' +
        '</div>' +
        '<div class="tp-rule-banner ' + (blok === 0 ? 'rule-a' : 'rule-b') + '"><b>Aturan Aktif:</b> ' + ruleText + '</div>' +
        '<div class="tp-game-stimulus">' + n + '</div>' +
        '<div class="tp-game-choices two">' +
          '<button type="button" class="tp-choice-btn primary" onclick="TP.gAturan(\'YA\')">YA</button>' +
          '<button type="button" class="tp-choice-btn" onclick="TP.gAturan(\'TIDAK\')">TIDAK</button>' +
        '</div>';
    }

    RUNTIME.game.nextQ = nextQ;
    nextQ();
  }

  function gAturan(val) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'aturan') return;
    if (val === g.ans) g.score++;
    g.i++;
    g.nextQ();
  }

  /* -- Game 3: Memori Grid (5 putaran, 4x4) -- */
  function runMemoriGrid() {
    renderGameShell('GIM KOGNITIF · MEMORI KERJA VISUAL', 'Memori Grid', 'Hafalkan posisi kotak yang disorot lalu klik kembali posisi yang sama.');
    RUNTIME.game = { id: 'grid', round: 1, score: 0, maxPossible: 0, target: new Set(), pick: new Set() };

    function nextRound() {
      const g = RUNTIME.game;
      const n = Math.min(3 + g.round, 8);
      g.maxPossible += n;
      const pool = [];
      for (let i = 0; i < 16; i++) pool.push(i);
      pool.sort(function () { return Math.random() - 0.5; });
      g.target = new Set(pool.slice(0, n));
      g.pick = new Set();

      const arena = $('tpGameArena');
      if (!arena) return;
      let cellsHtml = '';
      for (let i = 0; i < 16; i++) {
        cellsHtml += '<div class="tp-grid-cell ' + (g.target.has(i) ? 'is-lit' : '') + '"></div>';
      }
      arena.innerHTML =
        '<div class="tp-game-hud">' +
          '<span>Putaran: <b>' + g.round + '/5</b></span>' +
          '<span>Hafalkan <b>' + n + ' kotak</b></span>' +
        '</div>' +
        '<div class="tp-grid-board">' + cellsHtml + '</div>' +
        '<p class="tp-mini-note">Perhatikan polanya — kotak akan disembunyikan sebentar lagi...</p>';

      addTimeout(function () {
        if (!RUNTIME.game || RUNTIME.game.id !== 'grid') return;
        let pickHtml = '';
        for (let i = 0; i < 16; i++) {
          pickHtml += '<button type="button" class="tp-grid-cell is-btn" id="tpGridCell' + i + '" onclick="TP.gGridPick(' + i + ')"></button>';
        }
        arena.innerHTML =
          '<div class="tp-game-hud">' +
            '<span>Putaran: <b>' + g.round + '/5</b></span>' +
            '<span>Pilih <b>' + n + ' kotak</b> (<b id="tpGridSelCnt">0</b> dipilih)</span>' +
          '</div>' +
          '<div class="tp-grid-board">' + pickHtml + '</div>' +
          '<div class="tp-btn-row center" style="margin-top:1rem;">' +
            '<button type="button" class="btn btn-primary" onclick="TP.gGridCheck()">Periksa Jawaban ✓</button>' +
          '</div>';
      }, 1500);
    }

    RUNTIME.game.nextRound = nextRound;
    nextRound();
  }

  function gGridPick(idx) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'grid') return;
    const cell = $('tpGridCell' + idx);
    if (g.pick.has(idx)) {
      g.pick.delete(idx);
      if (cell) cell.classList.remove('is-pick');
    } else {
      g.pick.add(idx);
      if (cell) cell.classList.add('is-pick');
    }
    const cnt = $('tpGridSelCnt');
    if (cnt) cnt.textContent = String(g.pick.size);
  }

  function gGridCheck() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'grid') return;
    let benar = 0;
    let salah = 0;
    g.pick.forEach(function (x) {
      if (g.target.has(x)) benar++;
      else salah++;
    });
    g.score += Math.max(0, benar - salah);
    if (g.round >= 5) {
      return renderGameEnd('grid', 'Memori Grid', g.score, g.maxPossible, 'Skor menggabungkan jumlah kotak benar dikurangi penalti pilihan salah selama 5 putaran.');
    }
    g.round++;
    g.nextRound();
  }

  /* -- Game 4: Sprint Pola (15 deret) -- */
  function runSprintPola() {
    renderGameShell('GIM KOGNITIF · PENGENALAN POLA', 'Sprint Pola', 'Tentukan angka berikutnya pada 15 deret angka bervariasi.');
    RUNTIME.game = { id: 'pola', i: 0, score: 0, ans: 0 };

    function nextQ() {
      const g = RUNTIME.game;
      if (g.i >= 15) {
        return renderGameEnd('pola', 'Sprint Pola', g.score, 15, 'Skor pengenalan pola dari 15 deret progresif. Ulangi untuk mempertajam intuisi pola TIU & TKD.');
      }
      const k = g.i % 3;
      let seq = [];
      let ans = 0;
      if (k === 0) {
        const a = 2 + Math.floor(Math.random() * 8);
        const d = 2 + Math.floor(Math.random() * 5);
        seq = [a, a + d, a + 2 * d, a + 3 * d, a + 4 * d];
        ans = a + 5 * d;
      } else if (k === 1) {
        const a = 2 + Math.floor(Math.random() * 5);
        const s1 = a * 2 + 1;
        const s2 = s1 * 2 + 1;
        const s3 = s2 * 2 + 1;
        seq = [a, s1, s2, s3];
        ans = s3 * 2 + 1;
      } else {
        const a = 2 + Math.floor(Math.random() * 5);
        let d = 2;
        seq = [a];
        for (let j = 0; j < 4; j++) {
          seq.push(seq[seq.length - 1] + d);
          d += 2;
        }
        ans = seq[seq.length - 1] + d;
      }
      g.ans = ans;
      const opts = [ans, ans + 2, ans - 2, ans + 4].sort(function () { return Math.random() - 0.5; });

      const arena = $('tpGameArena');
      if (!arena) return;
      arena.innerHTML =
        '<div class="tp-game-hud">' +
          '<span>Soal: <b>' + (g.i + 1) + '/15</b></span>' +
          '<span>Skor: <b>' + g.score + '</b></span>' +
        '</div>' +
        '<div class="tp-game-stimulus sm">' + seq.join(' &middot; ') + ' &middot; <span class="tp-qmark">?</span></div>' +
        '<div class="tp-game-choices">' +
          opts.map(function (v) {
            return '<button type="button" class="tp-choice-btn" onclick="TP.gPola(' + v + ')">' + v + '</button>';
          }).join('') +
        '</div>';
    }

    RUNTIME.game.nextQ = nextQ;
    nextQ();
  }

  function gPola(val) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'pola') return;
    if (Number(val) === g.ans) g.score++;
    g.i++;
    g.nextQ();
  }

  /* -- Game 5: Tantangan Balon Risiko (8 putaran) -- */
  function runBalon() {
    renderGameShell('ASESMEN BERBASIS GIM · RISIKO', 'Tantangan Balon Risiko', 'Pompa balon untuk menaikkan potensi poin, atau simpan sebelum balon pecah!');
    RUNTIME.game = {
      id: 'balon',
      round: 0,
      saved: 0,
      pumps: 0,
      popped: 0,
      limit: 4 + Math.floor(Math.random() * 7),
      msg: 'Klik Pompa untuk menambah poin potensial.'
    };
    renderBalonState();
  }

  function renderBalonState() {
    const g = RUNTIME.game;
    const arena = $('tpGameArena');
    if (!g || !arena) return;
    const scale = (1 + g.pumps * 0.11).toFixed(2);
    arena.innerHTML =
      '<div class="tp-game-hud">' +
        '<span>Balon: <b>' + (g.round + 1) + '/8</b></span>' +
        '<span>Poin Tersimpan: <b>' + g.saved + '</b></span>' +
      '</div>' +
      '<div class="tp-balloon-wrap">' +
        '<div class="tp-balloon-ico" style="transform:scale(' + scale + ')">' +
          '<svg width="68" height="84" viewBox="0 0 68 84" fill="none" aria-hidden="true">' +
            '<defs><radialGradient id="balG" cx="35%" cy="30%" r="65%"><stop offset="0%" stop-color="#f28b96"/><stop offset="100%" stop-color="#c94a5a"/></radialGradient></defs>' +
            '<path d="M34 4C18 4 8 16 8 31C8 48 22 61 34 65C46 61 60 48 60 31C60 16 50 4 34 4Z" fill="url(#balG)"/>' +
            '<ellipse cx="24" cy="22" rx="5" ry="9" transform="rotate(-18 24 22)" fill="rgba(255,255,255,0.32)"/>' +
            '<path d="M31 65L37 65L34 70Z" fill="#b53a4a"/>' +
            '<path d="M34 70C34 75 31 79 35 83" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>' +
          '</svg>' +
        '</div>' +
        '<div class="tp-balloon-pts">Poin potensial: <b>' + g.pumps + '</b></div>' +
      '</div>' +
      '<p class="tp-mini-note">' + esc(g.msg) + '</p>' +
      '<div class="tp-game-choices two">' +
        '<button type="button" class="tp-choice-btn primary" onclick="TP.gBalonPompa()">Pompa (+1)</button>' +
        '<button type="button" class="tp-choice-btn" onclick="TP.gBalonSimpan()" ' + (g.pumps === 0 ? 'disabled' : '') + '>Simpan ' + g.pumps + ' Poin</button>' +
      '</div>';
  }

  function gBalonPompa() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'balon') return;
    g.pumps++;
    if (g.pumps >= g.limit) {
      g.popped++;
      g.round++;
      g.pumps = 0;
      if (g.round >= 8) {
        return renderGameEnd('balon', 'Tantangan Balon Risiko', g.saved, 80, 'Total poin tersimpan: ' + g.saved + ' (balon pecah ' + g.popped + ' kali). Latihan ini melatih keseimbangan antara potensi hadiah dan risiko.');
      }
      g.limit = 4 + Math.floor(Math.random() * 7);
      g.msg = 'Balon sebelumnya pecah! Poin potensial hangus, lanjut ke balon berikutnya.';
    } else {
      g.msg = 'Balon mengembang aman. Lanjut pompa atau amankan poin sekarang?';
    }
    renderBalonState();
  }

  function gBalonSimpan() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'balon' || g.pumps <= 0) return;
    const added = g.pumps;
    g.saved += added;
    g.round++;
    g.pumps = 0;
    if (g.round >= 8) {
      return renderGameEnd('balon', 'Tantangan Balon Risiko', g.saved, 80, 'Total poin tersimpan: ' + g.saved + ' (balon pecah ' + g.popped + ' kali). Skor adalah poin latihan yang diamankan.');
    }
    g.limit = 4 + Math.floor(Math.random() * 7);
    g.msg = '✓ Berhasil mengamankan +' + added + ' poin! Lanjut ke balon berikutnya.';
    renderBalonState();
  }

  /* -- Game 6: Pertukaran Kepercayaan (8 putaran) -- */
  function runPercaya() {
    renderGameShell('ASESMEN BERBASIS GIM · TIMBAL BALIK', 'Pertukaran Kepercayaan', 'Anda punya 10 token tiap putaran. Kiriman dikalikan 3 lalu mitra mengembalikan sebagian.');
    RUNTIME.game = { id: 'percaya', round: 0, score: 0, lastMsg: 'Pilih jumlah token yang ingin Anda kirim ke mitra simulasi.' };
    renderPercayaState();
  }

  function renderPercayaState() {
    const g = RUNTIME.game;
    const arena = $('tpGameArena');
    if (!g || !arena) return;
    arena.innerHTML =
      '<div class="tp-game-hud">' +
        '<span>Putaran: <b>' + (g.round + 1) + '/8</b></span>' +
        '<span>Skor Keseimbangan: <b>' + Math.round(g.score) + '</b></span>' +
      '</div>' +
      '<div class="tp-rule-banner rule-a">' + esc(g.lastMsg) + '</div>' +
      '<div class="tp-game-choices three">' +
        [0, 2, 4, 6, 8, 10].map(function (x) {
          return '<button type="button" class="tp-choice-btn" onclick="TP.gPercaya(' + x + ')">Kirim ' + x + ' Token</button>';
        }).join('') +
      '</div>';
  }

  function gPercaya(kirim) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'percaya') return;
    const rasio = [0.35, 0.5, 0.65, 0.8][g.round % 4];
    const kembali = Math.round(kirim * 3 * rasio);
    const bersih = 10 - kirim + kembali;
    g.score += Math.max(0, 10 - Math.abs(bersih - 15));
    g.round++;
    if (g.round >= 8) {
      return renderGameEnd('percaya', 'Pertukaran Kepercayaan', Math.round(g.score), 80, 'Gunakan latihan ini untuk mengamati konsistensi keputusan timbal balik dan keadilan Anda.');
    }
    g.lastMsg = 'Anda mengirim ' + kirim + ' token → mitra mengembalikan ' + kembali + ' token (hasil bersih putaran lalu: ' + bersih + ' token).';
    renderPercayaState();
  }

  /* -- Game 7: Memori Digit (8 putaran) -- */
  function runDigit() {
    renderGameShell('ASESMEN BERBASIS GIM · MEMORI KERJA', 'Memori Digit', 'Hafalkan urutan angka yang muncul sesaat lalu ketik ulang dengan tepat.');
    RUNTIME.game = { id: 'digit', round: 0, total: 8, score: 0, seq: '' };
    nextDigitRound();
  }

  function nextDigitRound() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'digit') return;
    if (g.round >= g.total) {
      return renderGameEnd('digit', 'Memori Digit', g.score, g.total, 'Anda mengingat tepat ' + g.score + ' dari ' + g.total + ' rangkaian angka (panjang 4 hingga 7 digit).');
    }
    const len = 4 + Math.floor(g.round / 2);
    let seq = '';
    for (let i = 0; i < len; i++) seq += String(Math.floor(Math.random() * 10));
    g.seq = seq;

    const arena = $('tpGameArena');
    if (!arena) return;
    arena.innerHTML =
      '<div class="tp-game-hud">' +
        '<span>Putaran: <b>' + (g.round + 1) + '/' + g.total + '</b></span>' +
        '<span>Panjang: <b>' + len + ' digit</b> · Skor: <b>' + g.score + '</b></span>' +
      '</div>' +
      '<div class="tp-game-stimulus mono" id="tpDigitShow">' + seq + '</div>' +
      '<div id="tpDigitInputWrap"><p class="tp-mini-note">Hafalkan angka di atas...</p></div>';

    addTimeout(function () {
      if (!RUNTIME.game || RUNTIME.game.id !== 'digit') return;
      const show = $('tpDigitShow');
      if (show) show.textContent = '•'.repeat(len);
      const wrap = $('tpDigitInputWrap');
      if (wrap) {
        wrap.innerHTML =
          '<form class="tp-digit-form" onsubmit="event.preventDefault(); TP.gDigitCheck();">' +
            '<input type="text" id="tpDigitInput" inputmode="numeric" autocomplete="off" placeholder="Ketik ' + len + ' digit angka..." required>' +
            '<button type="submit" class="btn btn-primary">Kirim Jawaban</button>' +
          '</form>';
        const inp = $('tpDigitInput');
        if (inp) inp.focus();
      }
    }, 950 + len * 140);
  }

  function gDigitCheck() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'digit') return;
    const inp = $('tpDigitInput');
    const val = inp ? inp.value.trim() : '';
    if (val === g.seq) g.score++;
    g.round++;
    nextDigitRound();
  }

  /* -- Game 8: Sinyal Berhenti (24 putaran Go/No-Go) -- */
  function runBerhenti() {
    renderGameShell('ASESMEN BERBASIS GIM · KONTROL IMPULS', 'Sinyal Berhenti (Go / No-Go)', 'Tekan tombol RESPON (atau SPASI) saat muncul JALAN. Tahan diri saat muncul BERHENTI!');
    RUNTIME.game = { id: 'berhenti', round: 0, total: 24, score: 0, stop: false, responded: false, started: false };
    const arena = $('tpGameArena');
    if (!arena) return;
    arena.innerHTML =
      '<div class="tp-stop-intro">' +
        '<p>Setiap sinyal tampil singkat (<b>±0,8 detik</b>). Fokus pada warna &amp; teks: <b style="color:#1f7a60;">JALAN = Tekan</b>, <b style="color:#b83b4a;">BERHENTI = Jangan Tekan</b>.</p>' +
        '<button type="button" class="btn btn-primary" onclick="TP.gStopBegin()">▶ Siap, Mulai 24 Sinyal!</button>' +
      '</div>';
  }

  function gStopBegin() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'berhenti') return;
    g.started = true;
    const arena = $('tpGameArena');
    if (!arena) return;
    arena.innerHTML =
      '<div class="tp-game-hud">' +
        '<span>Sinyal: <b id="tpStopCnt">1/' + g.total + '</b></span>' +
        '<span>Skor: <b id="tpStopScore">0</b></span>' +
      '</div>' +
      '<div id="tpStopCue" class="tp-signal-box">SIAP...</div>' +
      '<div class="tp-btn-row center">' +
        '<button type="button" id="tpStopBtn" class="btn btn-primary tp-big-respond" onclick="TP.gStopRespond()">RESPON (SPASI)</button>' +
      '</div>';

    setKeyHandler(function (e) {
      if (e.code === 'Space') {
        e.preventDefault();
        gStopRespond();
      }
    });
    nextStopRound();
  }

  function gStopRespond() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'berhenti' || !g.started || g.responded) return;
    g.responded = true;
    const btn = $('tpStopBtn');
    if (btn) btn.classList.add('is-pressed');
    if (!g.stop) g.score++;
  }

  function nextStopRound() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'berhenti') return;
    if (g.round >= g.total) {
      return renderGameEnd('berhenti', 'Sinyal Berhenti', g.score, g.total, 'Target latihan adalah respons cepat pada sinyal JALAN sekaligus menahan impuls saat sinyal BERHENTI.');
    }
    g.stop = Math.random() < 0.32;
    g.responded = false;
    const cue = $('tpStopCue');
    const cnt = $('tpStopCnt');
    const sc = $('tpStopScore');
    const btn = $('tpStopBtn');
    if (btn) btn.classList.remove('is-pressed');
    if (cnt) cnt.textContent = (g.round + 1) + '/' + g.total;
    if (sc) sc.textContent = String(g.score);
    if (cue) {
      cue.textContent = g.stop ? 'BERHENTI' : 'JALAN';
      cue.className = 'tp-signal-box ' + (g.stop ? 'is-stop' : 'is-go');
    }

    addTimeout(function () {
      if (!RUNTIME.game || RUNTIME.game.id !== 'berhenti') return;
      if (g.stop && !g.responded) g.score++;
      g.round++;
      nextStopRound();
    }, 820);
  }

  /* -- Game 9: Wajah Emosi (12 putaran) -- */
  const EMOSI_LIST = [
    ['🙂', 'Senang'],
    ['😟', 'Khawatir'],
    ['😠', 'Marah'],
    ['😮', 'Terkejut'],
    ['😐', 'Netral'],
    ['😔', 'Sedih']
  ];

  function runEmosi() {
    renderGameShell('ASESMEN BERBASIS GIM · EKSPRESI', 'Wajah Emosi', 'Kenali label emosi yang sesuai dari ikon ekspresi dengan cepat dan tepat.');
    const deck = EMOSI_LIST.concat(EMOSI_LIST).sort(function () { return Math.random() - 0.5; });
    RUNTIME.game = { id: 'emosi', round: 0, total: 12, score: 0, deck: deck };
    nextEmosiRound();
  }

  function nextEmosiRound() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'emosi') return;
    if (g.round >= g.total) {
      return renderGameEnd('emosi', 'Wajah Emosi', g.score, g.total, 'Anda mengenali ' + g.score + ' dari ' + g.total + ' ekspresi emosi dengan konsisten.');
    }
    const item = g.deck[g.round];
    g.target = item[1];
    const arena = $('tpGameArena');
    if (!arena) return;
    arena.innerHTML =
      '<div class="tp-game-hud">' +
        '<span>Soal: <b>' + (g.round + 1) + '/' + g.total + '</b></span>' +
        '<span>Skor: <b>' + g.score + '</b></span>' +
      '</div>' +
      '<div class="tp-face-big">' + item[0] + '</div>' +
      '<div class="tp-game-choices three">' +
        EMOSI_LIST.map(function (em) {
          return '<button type="button" class="tp-choice-btn" onclick="TP.gEmosi(\'' + em[1] + '\')">' + em[1] + '</button>';
        }).join('') +
      '</div>';
  }

  function gEmosi(choice) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'emosi') return;
    if (choice === g.target) g.score++;
    g.round++;
    nextEmosiRound();
  }

  /* -- Game 10: Kartu & Panah (20 putaran) -- */
  function runKartu() {
    renderGameShell('ASESMEN BERBASIS GIM · ATURAN KARTU', 'Kartu & Panah', 'Pilih kartu sesuai aturan LEBIH TINGGI atau LEBIH RENDAH yang terus berganti.');
    RUNTIME.game = { id: 'kartu', round: 0, total: 20, score: 0, ans: 0 };
    nextKartuRound();
  }

  function nextKartuRound() {
    const g = RUNTIME.game;
    if (!g || g.id !== 'kartu') return;
    if (g.round >= g.total) {
      return renderGameEnd('kartu', 'Kartu & Panah', g.score, g.total, 'Drill ini melatih kecepatan berpindah aturan antara memilih nilai lebih tinggi dan lebih rendah.');
    }
    const a = 1 + Math.floor(Math.random() * 9);
    let b = 1 + Math.floor(Math.random() * 9);
    while (b === a) b = 1 + Math.floor(Math.random() * 9);
    const higher = g.round % 2 === 0;
    g.ans = higher ? (a > b ? 0 : 1) : (a < b ? 0 : 1);

    const arena = $('tpGameArena');
    if (!arena) return;
    arena.innerHTML =
      '<div class="tp-game-hud">' +
        '<span>Soal: <b>' + (g.round + 1) + '/' + g.total + '</b></span>' +
        '<span>Skor: <b>' + g.score + '</b></span>' +
      '</div>' +
      '<div class="tp-rule-banner ' + (higher ? 'rule-a' : 'rule-b') + '">' +
        '<b>Aturan:</b> Pilih nilai <b>' + (higher ? 'LEBIH TINGGI' : 'LEBIH RENDAH') + '</b>' +
      '</div>' +
      '<div class="tp-two-cards">' +
        '<button type="button" class="tp-num-card" onclick="TP.gKartu(0)">' + a + '</button>' +
        '<button type="button" class="tp-num-card" onclick="TP.gKartu(1)">' + b + '</button>' +
      '</div>';
  }

  function gKartu(choice) {
    const g = RUNTIME.game;
    if (!g || g.id !== 'kartu') return;
    if (Number(choice) === g.ans) g.score++;
    g.round++;
    nextKartuRound();
  }

  function toggleEl(id) {
    const el = $(id);
    if (!el) return;
    el.classList.toggle('hidden');
    if (!el.classList.contains('hidden')) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  /* ---------- Public API ---------- */
  window.TP = {
    openTab: openTab,
    toggleEl: toggleEl,
    renderColorblindIntro: renderColorblindIntro,
    startColorblind: startColorblind,
    answerColorblind: answerColorblind,
    nextColorblind: nextColorblind,
    renderIQIntro: renderIQIntro,
    startIQ: startIQ,
    answerIQ: answerIQ,
    nextIQ: nextIQ,
    confirmExitIQ: confirmExitIQ,
    renderRiasecIntro: renderRiasecIntro,
    startRiasec: startRiasec,
    answerRiasec: answerRiasec,
    prevRiasec: prevRiasec,
    renderGamesHub: renderGamesHub,
    startGame: startGame,
    gKejar: gKejar,
    gAturan: gAturan,
    gGridPick: gGridPick,
    gGridCheck: gGridCheck,
    gPola: gPola,
    gBalonPompa: gBalonPompa,
    gBalonSimpan: gBalonSimpan,
    gPercaya: gPercaya,
    gDigitCheck: gDigitCheck,
    gStopBegin: gStopBegin,
    gStopRespond: gStopRespond,
    gEmosi: gEmosi,
    gKartu: gKartu
  };

  /* ---------- Boot ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    const initial = window.POTENSI_INITIAL_TAB || 'hub';
    openTab(initial, false);
  });
})();
