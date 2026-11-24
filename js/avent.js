// =========================================================
//  Calendrier de l'avent de Coeur de Meute
//  Une grande niche illustrée sous la neige : 24 volets à ouvrir,
//  des chiens à caresser, de la neige, des guirlandes…
//  - la case du jour s'ouvre, celles des jours passés aussi, les suivantes restent fermées ;
//  - lien direct vers une case : index.html?jour=5 (pour les réseaux sociaux) ;
//  - la date suivie est celle de la Belgique.
//  Le contenu des 24 cases se trouve dans js/avent-contenu.js
// =========================================================
(function () {
  const root = document.getElementById('avent');
  const data = window.AVENT;
  if (!root || !data) return;

  const art = root.querySelector('.scene__art');
  const wall = root.querySelector('.scene__wall');
  const fx = root.querySelector('.scene__fx');
  const status = root.querySelector('.avent__status');
  const chips = root.querySelector('.avent__chips');
  const counter = root.querySelector('.scene__pets');
  const hint = root.querySelector('.scene__hint');
  const soundBtn = root.querySelector('.scene__sound');
  const modal = document.getElementById('avent-modal');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const rand = (a, b) => a + Math.random() * (b - a);

  // ---------- Date du jour (heure belge) ----------
  const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Brussels', year: 'numeric', month: '2-digit', day: '2-digit' })
    .formatToParts(new Date()).reduce((o, p) => (o[p.type] = p.value, o), {});
  const todayIso = `${parts.year}-${parts.month}-${parts.day}`;
  const params = new URLSearchParams(location.search);

  // Mode test (seulement en local ou sur les adresses provisoires) : ?avent-test=12 fait comme si on était le 12 décembre
  const testHost = !location.protocol.startsWith('http') || /^(localhost|127\.0\.0\.1)$|\.netlify\.app$|\.hostingersite\.com$/.test(location.hostname);
  const testDay = testHost ? parseInt(params.get('avent-test'), 10) : NaN;

  let openUntil; // dernier jour ouvrable (0 = aucun)
  if (testDay >= 0 && testDay <= 25) openUntil = Math.min(testDay, 24);
  else if (todayIso > data.finAffichage) { root.hidden = true; return; }
  else if (todayIso < `${data.annee}-12-01`) openUntil = 0;
  else if (todayIso > `${data.annee}-12-24`) openUntil = 24;
  else openUntil = parseInt(parts.day, 10);
  const isToday = (n) => n === openUntil && (testDay >= 0 || todayIso <= `${data.annee}-12-24`);
  const tipText = (n) => (openUntil === 0 ? 'Patience, on ouvre le 1<sup>er</sup> décembre&nbsp;! 🐾' : `Patience&nbsp;! Ouverture le ${n} décembre 🐾`);

  // ---------- Cases déjà ouvertes (mémorisées sur cet appareil) ----------
  const KEY = `avent-${data.annee}`;
  let opened = [];
  try { opened = JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { /* navigation privée */ }
  const remember = (n) => {
    if (!opened.includes(n)) opened.push(n);
    try { localStorage.setItem(KEY, JSON.stringify(opened)); } catch (e) { /* ignore */ }
  };

  // ---------- Texte d'état ----------
  if (openUntil === 0) {
    const days = Math.round((Date.parse(`${data.annee}-12-01`) - Date.parse(todayIso)) / 86400000);
    status.innerHTML = `Le premier volet s’ouvre le <strong>1<sup>er</sup> décembre</strong> — plus que <strong>${days} jour${days > 1 ? 's' : ''}</strong>&nbsp;!`;
  } else if (openUntil < 24 || isToday(24)) {
    status.innerHTML = `Aujourd’hui, c’est le volet n°<strong>${openUntil}</strong> qui s’ouvre. Les jours passés restent ouverts&nbsp;!`;
  } else {
    status.innerHTML = 'Tous les volets sont ouverts. Joyeuses fêtes de toute la meute&nbsp;!';
  }

  // =========================================================
  //  Son (coupé par défaut)
  // =========================================================
  let audio = null, soundOn = false;
  soundBtn.addEventListener('click', () => {
    soundOn = !soundOn;
    soundBtn.setAttribute('aria-pressed', String(soundOn));
    soundBtn.innerHTML = soundOn ? '🔔 Son activé' : '🔕 Son coupé';
    if (soundOn && !audio) { try { audio = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { soundOn = false; } }
    if (soundOn) chime();
  });
  const tone = (f, t0, dur, type = 'sine', vol = .15, f2) => {
    if (!soundOn || !audio) return;
    const o = audio.createOscillator(), g = audio.createGain(), t = audio.currentTime + t0;
    o.type = type; o.frequency.setValueAtTime(f, t);
    if (f2) o.frequency.exponentialRampToValueAtTime(f2, t + dur);
    g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(vol, t + .01); g.gain.exponentialRampToValueAtTime(.0001, t + dur);
    o.connect(g).connect(audio.destination); o.start(t); o.stop(t + dur + .05);
  };
  const chime = () => [1318, 1568, 2093, 2637, 3136].forEach((f, i) => tone(f, i * .08, .6, 'triangle', .06));
  const pop = () => tone(rand(700, 1000), 0, .12, 'sine', .06, 1400);
  const bark = (small) => { const b = small ? 560 : 320; [0, .16].forEach((t) => { tone(b, t, .13, 'sawtooth', .06, b * .6); tone(b * 2, t, .09, 'square', .02, b); }); };
  const knock = () => tone(170, 0, .14, 'square', .05, 120);

  // =========================================================
  //  Effets : particules et bulles
  // =========================================================
  const PAW = '<svg viewBox="0 0 64 64"><ellipse cx="32" cy="42" rx="14" ry="12"/><ellipse cx="15" cy="25" rx="6" ry="8"/><ellipse cx="26" cy="16" rx="6" ry="8"/><ellipse cx="38" cy="16" rx="6" ry="8"/><ellipse cx="49" cy="25" rx="6" ry="8"/></svg>';
  const HEART = '<svg viewBox="0 0 64 64"><path d="M32 56 C6 38 6 14 20 12 C27 11 30 16 32 20 C34 16 37 11 44 12 C58 14 58 38 32 56 Z"/></svg>';
  const STAR = '<svg viewBox="0 0 64 64"><path d="M32 4 L39 24 L60 25 L43 38 L49 59 L32 47 L15 59 L21 38 L4 25 L25 24 Z"/></svg>';
  const SPARK = '<svg viewBox="0 0 64 64"><path d="M32 2 C34 24 40 30 62 32 C40 34 34 40 32 62 C30 40 24 34 2 32 C24 30 30 24 32 2 Z"/></svg>';
  const localXY = (clientX, clientY) => { const r = art.getBoundingClientRect(); return [clientX - r.left, clientY - r.top]; };
  const particle = (html, cls, x, y, o = {}) => {
    if (reduceMotion && !o.force) return;
    const p = document.createElement('span');
    p.className = `fx ${cls}`; p.innerHTML = html;
    p.style.left = `${x}px`; p.style.top = `${y}px`;
    p.style.setProperty('--dx', `${o.dx || 0}px`); p.style.setProperty('--dy', `${o.dy || -80}px`);
    p.style.setProperty('--rot', `${o.rot || 0}deg`); p.style.setProperty('--s', o.s || 1);
    p.style.setProperty('--d', `${o.d || 1.2}s`);
    fx.appendChild(p);
    p.addEventListener('animationend', () => p.remove());
  };
  const burstAt = (x, y, count = 36) => {
    const kinds = [[PAW, 'fx--paw'], [HEART, 'fx--heart'], [STAR, 'fx--star'], [SPARK, 'fx--spark']];
    for (let i = 0; i < count; i++) {
      const [h, c] = kinds[i % kinds.length], a = rand(0, Math.PI * 2), dist = rand(70, 230);
      particle(h, `${c} fx--burst`, x, y, { dx: Math.cos(a) * dist, dy: Math.sin(a) * dist - 70, rot: rand(-260, 260), s: rand(.6, 1.25), d: rand(.9, 1.5) });
    }
  };
  const bubble = (html, x, y, cls = '', ttl = 1800) => {
    const b = document.createElement('span');
    b.className = `scene__bubble ${cls}`; b.innerHTML = html;
    fx.appendChild(b);
    const w = art.clientWidth, half = Math.min(b.offsetWidth / 2 + 6, w / 2);
    b.style.left = `${Math.min(w - half, Math.max(half, x))}px`; b.style.top = `${y}px`;
    setTimeout(() => { b.classList.add('is-out'); setTimeout(() => b.remove(), 300); }, ttl);
    return b;
  };

  // =========================================================
  //  Les 24 volets
  // =========================================================
  const shutters = [];
  data.jours.forEach((jour, i) => {
    const n = i + 1;
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'volet'; b.dataset.day = n;
    b.style.setProperty('--i', i);
    const locked = n > openUntil;
    if (locked) b.classList.add('is-locked');
    if (opened.includes(n) && !locked) b.classList.add('is-open');
    if (isToday(n)) b.classList.add('is-today');
    if (n === 24) b.classList.add('is-24');
    b.setAttribute('aria-label', locked ? `Volet du ${n} décembre, pas encore ouvert` : `Ouvrir le volet du ${n} décembre`);
    b.innerHTML = `
      <span class="volet__glow" aria-hidden="true"></span>
      <img class="volet__frame" src="assets/avent/fenetre.webp" alt="" draggable="false">
      <span class="volet__inside" aria-hidden="true">${esc(jour.emoji || '🎁')}</span>
      <span class="volet__door" aria-hidden="true">
        <span class="volet__num">${n}</span>
        ${locked ? '<svg class="volet__lock" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2.5"/><path d="M8 11 V8 a4 4 0 0 1 8 0 V11"/></svg>' : ''}
      </span>
      ${isToday(n) && !opened.includes(n) ? '<span class="volet__badge">Ouvre-moi&nbsp;!</span>' : ''}`;
    wall.appendChild(b);
    shutters.push(b);
  });

  const centerOf = (el) => { const r = el.getBoundingClientRect(); return localXY(r.left + r.width / 2, r.top + r.height / 2); };

  const openDay = (n, opts = {}) => {
    const b = shutters[n - 1];
    if (!b) return;
    if (n > openUntil) {
      b.classList.remove('is-shake'); void b.offsetWidth; b.classList.add('is-shake');
      const [x, y] = centerOf(b);
      bubble(tipText(n), x, y + b.offsetHeight * .75, 'scene__bubble--tip scene__bubble--below', 2200);
      knock();
      return;
    }
    const was = b.classList.contains('is-open');
    b.classList.add('is-open');
    b.querySelector('.volet__badge')?.remove();
    remember(n); syncChip(n);
    if (was || opts.instant || reduceMotion) { showModal(n); return; }
    b.classList.add('is-opening');
    chime();
    setTimeout(() => {
      const [x, y] = centerOf(b);
      burstAt(x, y);
      dogs.forEach((d, k) => setTimeout(() => cheer(d, 1400), k * 90));
      bark(false);
    }, 420);
    setTimeout(() => { b.classList.remove('is-opening'); showModal(n); }, 1500);
  };
  wall.addEventListener('click', (e) => { const b = e.target.closest('.volet'); if (b) openDay(+b.dataset.day); });

  // ---------- Pastilles 1 à 24 (accès rapide) ----------
  const chipEls = shutters.map((s, i) => {
    const n = i + 1, c = document.createElement('button');
    c.type = 'button'; c.className = 'avent__chip'; c.textContent = n;
    if (n > openUntil) c.classList.add('is-locked');
    if (isToday(n)) c.classList.add('is-today');
    if (opened.includes(n) && n <= openUntil) c.classList.add('is-opened');
    c.setAttribute('aria-label', n > openUntil ? `${n} décembre : pas encore ouvert` : `Ouvrir la case du ${n} décembre`);
    c.addEventListener('click', () => {
      if (n > openUntil) { c.classList.remove('is-shake'); void c.offsetWidth; c.classList.add('is-shake'); }
      openDay(n);
    });
    chips.appendChild(c); return c;
  });
  function syncChip(n) { chipEls[n - 1]?.classList.add('is-opened'); }

  // =========================================================
  //  Les chiens
  // =========================================================
  const PHRASES = ['Wouf&nbsp;!', 'Wouf wouf&nbsp;! 🐾', 'Encore&nbsp;! ❤️', 'Joyeux Noël&nbsp;! 🎄', 'Une caresse&nbsp;?', 'On joue&nbsp;? 🎾', 'Merci&nbsp;! 🥰', 'Tu as une friandise&nbsp;? 🦴'];
  let pets = 0;
  const dogs = [...root.querySelectorAll('.chien')].map((el) => ({ el, name: el.dataset.name, small: el.dataset.small === '1', sleepy: el.classList.contains('is-asleep'), calm: 0, nextHeart: 0 }));

  // une seule bulle à la fois par chien
  const say = (d, ...args) => { d.bubble?.remove(); d.bubble = bubble(...args); };
  function cheer(d, ms = 1200) {
    d.el.classList.add('is-happy');
    if (d.sleepy) d.el.classList.remove('is-asleep');
    d.el.classList.remove('is-hop'); void d.el.offsetWidth; d.el.classList.add('is-hop');
    clearTimeout(d.calm);
    d.calm = setTimeout(() => calm(d), ms);
  }
  function calm(d) {
    d.el.classList.remove('is-happy');
    if (d.sleepy) d.calm = setTimeout(() => d.el.classList.add('is-asleep'), 6000);
  }
  const pet = (d, clientX, clientY, quiet) => {
    const now = performance.now();
    const wasAsleep = d.el.classList.contains('is-asleep');
    d.el.classList.add('is-happy', 'is-petted');
    d.el.classList.remove('is-asleep');
    clearTimeout(d.calm); clearTimeout(d.petEnd);
    d.petEnd = setTimeout(() => d.el.classList.remove('is-petted'), 260);
    d.calm = setTimeout(() => calm(d), 1100);
    const [x, y] = localXY(clientX, clientY);
    if (wasAsleep && !quiet) say(d, `${d.name} se réveille… 🥱`, x, y - 30, '', 1600);
    if (now < d.nextHeart) return;
    d.nextHeart = now + 130;
    particle(HEART, 'fx--heart fx--float', x, y, { dx: rand(-40, 40), dy: rand(-110, -70), rot: rand(-30, 30), s: rand(.7, 1.1), d: 1.3 });
    pets++;
    counter.innerHTML = `❤️ <strong>${pets}</strong> caresse${pets > 1 ? 's' : ''}`;
    counter.classList.add('is-visible');
    counter.classList.remove('is-bump'); void counter.offsetWidth; counter.classList.add('is-bump');
    hint?.classList.add('is-done');
    pop();
    if (pets % 25 === 0) {
      say(d, `${d.name} vous adore&nbsp;! 🥰`, x, y - 40, 'scene__bubble--love', 2200);
      for (let k = 0; k < 14; k++) particle(HEART, 'fx--heart fx--burst', x, y, { dx: rand(-140, 140), dy: rand(-200, -60), rot: rand(-90, 90), s: rand(.6, 1.2), d: 1.4 });
      cheer(d, 1600); bark(d.small);
    }
  };
  dogs.forEach((d) => {
    let down = null;
    d.el.addEventListener('pointerdown', (e) => { down = { x: e.clientX, y: e.clientY }; d.el.setPointerCapture?.(e.pointerId); });
    d.el.addEventListener('pointermove', (e) => {
      if (e.pointerType === 'mouse' || down) {
        const r = d.el.getBoundingClientRect();
        if (e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom) pet(d, e.clientX, e.clientY);
      }
    });
    d.el.addEventListener('pointerup', (e) => {
      if (down && Math.hypot(e.clientX - down.x, e.clientY - down.y) < 10) {
        const [x] = localXY(e.clientX, e.clientY);
        const wasAsleep = d.el.classList.contains('is-asleep');
        cheer(d, 1500); pet(d, e.clientX, e.clientY, true); bark(d.small);
        const r = d.el.getBoundingClientRect(), [, top] = localXY(0, r.top);
        say(d, wasAsleep ? `${d.name} se réveille… 🥱` : PHRASES[Math.floor(Math.random() * PHRASES.length)], x, top + r.height * .12, '', 1500);
      }
      down = null;
    });
    d.el.addEventListener('pointercancel', () => { down = null; });
    d.el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); const r = d.el.getBoundingClientRect(); cheer(d, 1500); pet(d, r.left + r.width / 2, r.top + r.height / 3); bark(d.small); } });
  });
  // le chiot fait des petits bonds de temps en temps
  if (!reduceMotion) setInterval(() => { const d = dogs.find((x) => x.name === 'Biscuit'); if (d && !document.hidden && !d.el.classList.contains('is-happy')) { d.el.classList.remove('is-hop'); void d.el.offsetWidth; d.el.classList.add('is-hop'); } }, 4200);

  // =========================================================
  //  Neige qui tombe (canvas)
  // =========================================================
  const canvas = root.querySelector('.scene__snow');
  if (canvas && !reduceMotion) {
    const ctx = canvas.getContext('2d');
    let flakes = [], w = 0, h = 0, running = false;
    const resize = () => {
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = art.clientWidth; h = art.clientHeight;
      canvas.width = w * dpr; canvas.height = h * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const count = Math.round(Math.min(220, w * h / 6000));
      flakes = Array.from({ length: count }, () => ({ x: rand(0, w), y: rand(0, h), r: rand(.6, 3.2), s: rand(.3, 1), d: rand(0, 6) }));
    };
    const frame = () => {
      if (!running) return;
      ctx.clearRect(0, 0, w, h);
      flakes.forEach((f) => {
        f.d += .012; f.y += f.s * (f.r * .45 + .35); f.x += Math.sin(f.d) * .45;
        if (f.y > h + 5) { f.y = -5; f.x = rand(0, w); }
        ctx.globalAlpha = .45 + f.r / 6; ctx.beginPath(); ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2); ctx.fillStyle = '#fff'; ctx.fill();
      });
      requestAnimationFrame(frame);
    };
    resize();
    new ResizeObserver(resize).observe(art);
    new IntersectionObserver(([e]) => { const was = running; running = e.isIntersecting; if (running && !was) frame(); }).observe(art);
  }

  // =========================================================
  //  Fenêtre de la case
  // =========================================================
  let current = 0;
  const showModal = (n) => {
    const jour = data.jours[n - 1];
    current = n;
    const media = jour.image
      ? `<img src="${esc(jour.image)}" alt="${esc(jour.titre)}">`
      : `<span class="avent-card__emoji" aria-hidden="true">${esc(jour.emoji)}</span>`;
    const m = modal.querySelector('.avent-card__media');
    m.innerHTML = `${media}<span class="avent-card__day"><small>Volet</small>${n}<small>décembre</small></span>`;
    m.className = `avent-card__media avent-card__media--${n % 6}${jour.image ? ' has-img' : ''}`;
    modal.querySelector('.avent-card__title').textContent = jour.titre;
    modal.querySelector('.avent-card__text').textContent = jour.texte;
    const cta = modal.querySelector('.avent-card__cta');
    if (jour.bouton) { cta.hidden = false; cta.textContent = jour.bouton.texte; cta.href = jour.bouton.lien; } else cta.hidden = true;
    modal.querySelector('[data-nav="prev"]').disabled = n <= 1;
    modal.querySelector('[data-nav="next"]').disabled = n >= openUntil;
    modal.querySelector('.avent-card__share-done').textContent = '';
    modal.hidden = false;
    modal.classList.remove('is-in'); void modal.offsetWidth; modal.classList.add('is-in');
    document.documentElement.classList.add('avent-lock');
    modal.querySelector('.avent-card__close').focus({ preventScroll: true });
  };
  const closeModal = () => {
    modal.hidden = true;
    document.documentElement.classList.remove('avent-lock');
    shutters[current - 1]?.focus({ preventScroll: true });
    if (params.has('jour')) history.replaceState(null, '', location.pathname + location.search.replace(/[?&]jour=\d+/, '').replace(/^&/, '?') + '#avent');
  };
  modal.addEventListener('click', (e) => {
    if (e.target === modal || e.target.closest('.avent-card__close')) closeModal();
    const nav = e.target.closest('[data-nav]');
    if (nav && !nav.disabled) {
      const n = current + (nav.dataset.nav === 'next' ? 1 : -1);
      shutters[n - 1].classList.add('is-open'); shutters[n - 1].querySelector('.volet__badge')?.remove();
      remember(n); syncChip(n); showModal(n);
    }
    if (e.target.closest('.avent-card__share')) share();
  });
  document.addEventListener('keydown', (e) => {
    if (modal.hidden) return;
    if (e.key === 'Escape') closeModal();
    if (e.key === 'ArrowRight' && current < openUntil) modal.querySelector('[data-nav="next"]').click();
    if (e.key === 'ArrowLeft' && current > 1) modal.querySelector('[data-nav="prev"]').click();
  });
  const share = () => {
    const url = `${location.origin}${location.pathname}?jour=${current}#avent`;
    const text = `Volet n°${current} du calendrier de l'avent de Coeur de Meute 🐾`;
    const done = modal.querySelector('.avent-card__share-done');
    if (navigator.share) { navigator.share({ title: 'Coeur de Meute', text, url }).catch(() => {}); return; }
    (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => {
      done.textContent = 'Lien copié !';
      setTimeout(() => { done.textContent = ''; }, 2500);
    }).catch(() => { done.textContent = url; });
  };

  // pour les tests et les aperçus
  window.AVENT_SCENE = { openDay, dogs, pet, cheer };

  // ---------- Lien direct depuis les réseaux sociaux : ?jour=5 ----------
  const wanted = parseInt(params.get('jour') || (location.hash.match(/^#jour-(\d+)/) || [])[1], 10);
  if (wanted >= 1 && wanted <= 24) {
    root.scrollIntoView({ behavior: 'instant', block: 'start' });
    setTimeout(() => openDay(wanted), 900);
  }
})();
