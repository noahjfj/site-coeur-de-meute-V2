// =========================================================
//  Calendrier de l'avent : 24 niches en 3D (CSS) sous la neige
//  On peut caresser les chiens des niches ouvertes.
//  - la niche du jour s'ouvre, celles des jours passés aussi, les suivantes restent fermées ;
//  - lien direct vers une case : index.html?jour=5 (pour les réseaux sociaux) ;
//  - la date suivie est celle de la Belgique.
// =========================================================
(function () {
  const root = document.getElementById('avent');
  const data = window.AVENT;
  if (!root || !data) return;

  const grid = root.querySelector('.avent__grid');
  const status = root.querySelector('.avent__status');
  const modal = document.getElementById('avent-modal');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  // ---------- Date du jour (heure belge) ----------
  const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Brussels', year: 'numeric', month: '2-digit', day: '2-digit' })
    .formatToParts(new Date()).reduce((o, p) => (o[p.type] = p.value, o), {});
  const todayIso = `${parts.year}-${parts.month}-${parts.day}`;
  const params = new URLSearchParams(location.search);

  // Mode test (seulement en local ou sur l'adresse provisoire) : ?avent-test=12 fait comme si on était le 12 décembre
  const testHost = !location.protocol.startsWith('http') || /^(localhost|127\.0\.0\.1)$|\.netlify\.app$|\.hostingersite\.com$/.test(location.hostname);
  const testDay = testHost ? parseInt(params.get('avent-test'), 10) : NaN;

  let openUntil; // dernier jour ouvrable (0 = aucun)
  if (testDay >= 0 && testDay <= 25) {
    openUntil = Math.min(testDay, 24);
  } else if (todayIso > data.finAffichage) {
    root.hidden = true; return;
  } else if (todayIso < `${data.annee}-12-01`) {
    openUntil = 0;
  } else if (todayIso > `${data.annee}-12-24`) {
    openUntil = 24;
  } else {
    openUntil = parseInt(parts.day, 10);
  }
  const isToday = (n) => n === openUntil && (testDay >= 0 || todayIso <= `${data.annee}-12-24`);

  // ---------- Niches déjà ouvertes (mémorisées sur cet appareil) ----------
  const KEY = `avent-${data.annee}`;
  let opened = [];
  try { opened = JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { /* navigation privée */ }
  const remember = (n) => {
    if (!opened.includes(n)) opened.push(n);
    try { localStorage.setItem(KEY, JSON.stringify(opened)); } catch (e) { /* ignore */ }
  };

  // ---------- Construction des niches ----------
  const COLORS = ['rust', 'blue', 'sand', 'brown', 'ink', 'copper'];
  const DOGS = [ // couleurs du chien qui sort de la niche : [pelage, oreilles, museau]
    ['#c98a62', '#8b6a52', '#f2dcc6'], ['#f4f0ea', '#c9a98a', '#ffffff'], ['#3d3a39', '#22201f', '#8b7b6e'],
    ['#d9b98f', '#a8805c', '#f6e8d4'], ['#8b6a52', '#5c4432', '#d8b99c'], ['#e9e2d8', '#3d3a39', '#ffffff'],
  ];

  // Chaque chien a son look : oreilles tombantes ou dressées, bonnet, écharpe ou noeud
  const LOOKS = ['hat', 'scarf', 'bow', 'none'];
  const dogSvg = (n) => {
    const [fur, ear, snout] = DOGS[n % DOGS.length];
    const pointy = n % 3 === 1;
    const look = n === 24 ? 'hat' : LOOKS[n % 4];
    const ears = pointy
      ? `<g class="dog__ear dog__ear--l"><path d="M24 34 L20 6 L40 22 Z" fill="${ear}"/><path d="M26 28 L24 13 L35 22 Z" fill="#e9b3a6"/></g>
         <g class="dog__ear dog__ear--r"><path d="M76 34 L80 6 L60 22 Z" fill="${ear}"/><path d="M74 28 L76 13 L65 22 Z" fill="#e9b3a6"/></g>`
      : `<g class="dog__ear dog__ear--l"><path d="M22 20 C8 22 4 48 14 58 C20 52 26 40 30 28 Z" fill="${ear}"/></g>
         <g class="dog__ear dog__ear--r"><path d="M78 20 C92 22 96 48 86 58 C80 52 74 40 70 28 Z" fill="${ear}"/></g>`;
    const extra = {
      hat: '<g class="dog__hat"><path d="M27 22 C34 4 58 -6 74 6 C66 8 60 14 58 20 Z" fill="#c0392b"/><circle cx="76" cy="6" r="6" fill="#fff"/><rect x="22" y="17" width="56" height="9" rx="4.5" fill="#fff"/></g>',
      scarf: '<path d="M26 78 Q50 90 74 78 L74 86 Q50 98 26 86 Z" fill="#2e6b47"/><path d="M60 84 L66 100 L56 100 L54 86 Z" fill="#2e6b47"/><path d="M30 80 L30 88 M40 83 L40 91 M50 84 L50 92 M60 83 L60 91 M70 80 L70 88" stroke="#24553a" stroke-width="2"/>',
      bow: '<g transform="translate(70 20) rotate(15)"><path d="M0 0 L-10 -7 L-10 7 Z M0 0 L10 -7 L10 7 Z" fill="#a65a43"/><circle r="3" fill="#8a4733"/></g>',
      none: '<path d="M30 80 Q50 88 70 80" stroke="#a65a43" stroke-width="5" fill="none" stroke-linecap="round"/><circle cx="50" cy="87" r="4" fill="#f4c95d"/>',
    }[look];
    return `<svg class="niche__dog" viewBox="0 -8 100 108" aria-hidden="true">
      ${ears}
      <path d="M50 12 C26 12 20 34 22 52 C24 74 38 86 50 86 C62 86 76 74 78 52 C80 34 74 12 50 12 Z" fill="${fur}"/>
      <ellipse cx="50" cy="66" rx="17" ry="13" fill="${snout}"/>
      <g class="dog__eyes"><circle cx="38" cy="46" r="4.6" fill="#2a2522"/><circle cx="62" cy="46" r="4.6" fill="#2a2522"/>
      <circle cx="39.6" cy="44.4" r="1.5" fill="#fff"/><circle cx="63.6" cy="44.4" r="1.5" fill="#fff"/></g>
      <g class="dog__happy"><path d="M33 47 Q38 41 43 47 M57 47 Q62 41 67 47" stroke="#2a2522" stroke-width="2.6" fill="none" stroke-linecap="round"/>
      <ellipse cx="30" cy="57" rx="5" ry="3" fill="#ff8fa3" opacity=".7"/><ellipse cx="70" cy="57" rx="5" ry="3" fill="#ff8fa3" opacity=".7"/></g>
      <path d="M44 59 Q50 55 56 59 Q55 65 50 66 Q45 65 44 59 Z" fill="#2a2522"/>
      <path d="M50 66 V70 M43 71 Q50 77 57 71" stroke="#2a2522" stroke-width="2" fill="none" stroke-linecap="round"/>
      <path class="dog__tongue" d="M46 73 Q50 86 54 73 Z" fill="#e57b7b"/>
      ${extra}
    </svg>`;
  };

  // guirlande lumineuse le long du toit (petites ampoules posées sur le bord du toit)
  const lightsHtml = (() => {
    const colors = ['#ff5d5d', '#ffd34d', '#5ecbff', '#7ee07a', '#ff9de1'];
    let dots = '';
    for (let k = 0; k <= 12; k++) {
      const t = k / 12, x = 7 + t * 86, y = 92 - (1 - Math.abs(t - .5) * 2) * 70 + (k % 2 ? 4 : 0);
      dots += `<i style="left:${x.toFixed(1)}%;top:${y.toFixed(1)}%;--c:${colors[k % colors.length]};--k:${k}"></i>`;
    }
    return `<span class="niche__lights" aria-hidden="true">${dots}</span>`;
  })();

  const lockSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2.5"/><path d="M8 11 V8 a4 4 0 0 1 8 0 V11" fill="none"/></svg>';

  data.jours.forEach((jour, i) => {
    const n = i + 1;
    const color = n === 24 ? 'gold' : COLORS[(i * 5) % COLORS.length];
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `niche niche--${color}${n === 24 ? ' niche--big' : ''}`;
    btn.dataset.day = n;
    btn.style.setProperty('--i', i);
    const state = n > openUntil ? 'locked' : opened.includes(n) ? 'opened' : 'ready';
    btn.classList.add(`is-${state}`);
    if (isToday(n)) btn.classList.add('is-today');
    btn.setAttribute('aria-label', n > openUntil ? `Niche du ${n} décembre, pas encore ouverte` : `Ouvrir la niche du ${n} décembre`);
    btn.innerHTML = `
      <span class="niche__body">
        <span class="niche__roof-side"></span>
        <span class="niche__side"></span>
        <span class="niche__roof"></span>
        ${lightsHtml}
        <span class="niche__gable"><span class="niche__plate">${n}</span></span>
        <span class="niche__wall">
          <span class="niche__hole">${dogSvg(n)}<span class="niche__item">${esc(jour.emoji || '🎁')}</span></span>
          <span class="niche__door"><span class="niche__door-front"><span class="niche__num">${n}</span><span class="niche__lock">${lockSvg}</span></span><span class="niche__door-back"></span></span>
        </span>
        <span class="niche__bowl" aria-hidden="true"></span>
      </span>
      <span class="niche__rays" aria-hidden="true"></span>
      ${isToday(n) && !opened.includes(n) ? '<span class="niche__badge">Ouvre-moi&nbsp;!</span>' : ''}
      <span class="niche__tip" role="status"></span>`;
    grid.appendChild(btn);
  });

  // ---------- Texte d'état ----------
  if (openUntil === 0) {
    const days = Math.round((Date.parse(`${data.annee}-12-01`) - Date.parse(todayIso)) / 86400000);
    status.innerHTML = `La première niche s’ouvre le <strong>1<sup>er</sup> décembre</strong> — plus que <strong>${days} jour${days > 1 ? 's' : ''}</strong>&nbsp;!`;
  } else if (openUntil < 24 || isToday(24)) {
    status.innerHTML = `Aujourd’hui, c’est la niche n°<strong>${openUntil}</strong> qui s’ouvre. Les jours passés restent ouverts&nbsp;!`;
    if (!opened.includes(openUntil)) {
      const go = document.createElement('button');
      go.type = 'button'; go.className = 'btn avent__go';
      go.innerHTML = `Ouvrir la niche du jour <span aria-hidden="true">↓</span>`;
      go.addEventListener('click', () => {
        const el = grid.querySelector(`.niche:nth-child(${openUntil})`);
        el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        el.classList.remove('is-called'); void el.offsetWidth; el.classList.add('is-called');
        setTimeout(() => el.focus({ preventScroll: true }), 700);
      });
      status.after(go);
    }
  } else {
    status.innerHTML = 'Toutes les niches sont ouvertes. Joyeuses fêtes de toute la meute&nbsp;!';
  }

  // ---------- Effet 3D : la scène suit la souris / l'inclinaison ----------
  if (!reduceMotion) {
    let raf = 0;
    const tilt = (x, y) => {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        root.style.setProperty('--ry', `${-16 + x * 14}deg`);
        root.style.setProperty('--rx', `${-8 - y * 8}deg`);
      });
    };
    root.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse') return;
      const r = root.getBoundingClientRect();
      tilt((e.clientX - r.left) / r.width - 0.5, (e.clientY - r.top) / r.height - 0.5);
    });
    root.addEventListener('pointerleave', () => tilt(0, 0));

    // Téléphone / tablette : la scène bouge quand on fait défiler la page…
    const touch = window.matchMedia('(hover: none)').matches;
    let gyro = false;
    const onScroll = () => {
      const r = root.getBoundingClientRect(), vh = window.innerHeight || 1;
      const p = Math.max(-1, Math.min(1, (r.top + r.height / 2 - vh / 2) / vh));
      root.style.setProperty('--sy', p.toFixed(3));
      if (touch && !gyro) tilt(Math.sin(p * 3) * 0.6, p * 0.8);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    // … et suit l'inclinaison du téléphone quand c'est possible.
    if (touch && 'DeviceOrientationEvent' in window) {
      const onTilt = (e) => {
        if (e.gamma == null) return;
        gyro = true;
        tilt(Math.max(-1, Math.min(1, e.gamma / 35)) * 0.7, Math.max(-1, Math.min(1, (e.beta - 45) / 40)) * 0.7);
      };
      const listen = () => window.addEventListener('deviceorientation', onTilt);
      if (typeof DeviceOrientationEvent.requestPermission === 'function') {
        root.addEventListener('click', () => {
          DeviceOrientationEvent.requestPermission().then((s) => { if (s === 'granted') listen(); }).catch(() => {});
        }, { once: true });
      } else listen();
    }
  }

  // ---------- Les niches tombent quand elles arrivent à l'écran ----------
  if (!reduceMotion && 'IntersectionObserver' in window) {
    grid.classList.add('is-waiting');
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      if (!e.isIntersecting) return;
      const el = e.target, cols = getComputedStyle(grid).gridTemplateColumns.split(' ').length || 1;
      el.style.animationDelay = `${(Number(el.style.getPropertyValue('--i')) % cols) * 90}ms`;
      el.classList.add('is-in');
      io.unobserve(el);
    }), { rootMargin: '0px 0px -8% 0px' });
    grid.querySelectorAll('.niche').forEach((el) => io.observe(el));
  }

  // ---------- Les boules se balancent quand on les touche ----------
  root.querySelectorAll('.avent__deco--hang').forEach((b) => {
    const hit = () => { b.classList.remove('is-hit'); void b.offsetWidth; b.classList.add('is-hit'); };
    b.addEventListener('pointerdown', hit);
    b.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') hit(); });
    b.addEventListener('animationend', (e) => { if (e.animationName === 'swing-hit') b.classList.remove('is-hit'); });
  });
  // la porte du jour se rappelle à l'ordre
  grid.addEventListener('animationend', (e) => { if (e.animationName === 'called') e.target.closest('.niche')?.classList.remove('is-called'); });

  // ---------- Neige ----------
  const canvas = root.querySelector('.avent__snow');
  if (canvas && !reduceMotion) {
    const ctx = canvas.getContext('2d');
    let flakes = [], w = 0, h = 0, running = false;
    const resize = () => {
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = root.clientWidth; h = root.clientHeight;
      canvas.width = w * dpr; canvas.height = h * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const count = Math.round(Math.min(160, w * h / 9000));
      flakes = Array.from({ length: count }, () => ({ x: Math.random() * w, y: Math.random() * h, r: Math.random() * 2.4 + .6, s: Math.random() * .6 + .3, d: Math.random() * Math.PI * 2 }));
    };
    const frame = () => {
      if (!running) return;
      ctx.clearRect(0, 0, w, h);
      ctx.fillStyle = 'rgba(142,162,181,.55)';
      ctx.beginPath();
      flakes.forEach((f) => {
        f.d += .01; f.y += f.s * (f.r * .6 + .4); f.x += Math.sin(f.d) * .4;
        if (f.y > h + 5) { f.y = -5; f.x = Math.random() * w; }
        ctx.moveTo(f.x, f.y); ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
      });
      ctx.fill();
      requestAnimationFrame(frame);
    };
    resize();
    window.addEventListener('resize', resize);
    new IntersectionObserver(([e]) => { const was = running; running = e.isIntersecting; if (running && !was) frame(); }).observe(root);
  }

  // ---------- Particules (pattes, étoiles, coeurs) ----------
  const SHAPES = {
    paw: '<svg viewBox="0 0 64 64"><ellipse cx="32" cy="42" rx="14" ry="12"/><ellipse cx="15" cy="25" rx="6" ry="8"/><ellipse cx="26" cy="16" rx="6" ry="8"/><ellipse cx="38" cy="16" rx="6" ry="8"/><ellipse cx="49" cy="25" rx="6" ry="8"/></svg>',
    heart: '<svg viewBox="0 0 64 64"><path d="M32 56 C6 38 6 14 20 12 C27 11 30 16 32 20 C34 16 37 11 44 12 C58 14 58 38 32 56 Z"/></svg>',
    star: '<svg viewBox="0 0 64 64"><path d="M32 4 L39 24 L60 25 L43 38 L49 59 L32 47 L15 59 L21 38 L4 25 L25 24 Z"/></svg>',
    spark: '<svg viewBox="0 0 64 64"><path d="M32 2 C34 24 40 30 62 32 C40 34 34 40 32 62 C30 40 24 34 2 32 C24 30 30 24 32 2 Z"/></svg>',
  };
  const particle = (kind, x, y, dx, dy, size, dur) => {
    const p = document.createElement('span');
    p.className = `avent-particle avent-particle--${kind}`;
    p.innerHTML = SHAPES[kind];
    p.style.left = `${x}px`; p.style.top = `${y}px`;
    p.style.setProperty('--dx', `${dx}px`); p.style.setProperty('--dy', `${dy}px`);
    p.style.setProperty('--rot', `${Math.random() * 500 - 250}deg`);
    p.style.setProperty('--d', `${dur}s`);
    p.style.width = p.style.height = `${size}px`;
    document.body.appendChild(p);
    p.addEventListener('animationend', () => p.remove());
  };
  const burst = (el) => {
    if (reduceMotion) return;
    const r = el.getBoundingClientRect();
    const cx = r.left + r.width / 2, cy = r.top + r.height * .6;
    const kinds = ['paw', 'heart', 'star', 'spark'];
    for (let k = 0; k < 30; k++) {
      const angle = Math.random() * Math.PI * 2, dist = 80 + Math.random() * 150;
      particle(kinds[k % 4], cx, cy, Math.cos(angle) * dist, Math.sin(angle) * dist - 70, 14 + Math.random() * 16, 1 + Math.random() * .5);
    }
  };

  // ---------- Caresser les chiens des niches ouvertes ----------
  let pets = 0, nextHeart = 0;
  const pets$ = root.querySelector('.avent__pets');
  const pet = (btn, x, y) => {
    btn.classList.add('is-petted');
    clearTimeout(btn._pet); btn._pet = setTimeout(() => btn.classList.remove('is-petted'), 700);
    const now = performance.now();
    if (now < nextHeart) return;
    nextHeart = now + 140;
    if (!reduceMotion) particle('heart', x, y, Math.random() * 60 - 30, -70 - Math.random() * 40, 16 + Math.random() * 8, 1.2);
    pets++;
    pets$.innerHTML = `❤️ <strong>${pets}</strong> caresse${pets > 1 ? 's' : ''} donnée${pets > 1 ? 's' : ''}`;
    pets$.hidden = false;
    pets$.classList.remove('is-bump'); void pets$.offsetWidth; pets$.classList.add('is-bump');
  };
  let touchPet = false;
  grid.addEventListener('pointerdown', (e) => { touchPet = e.pointerType !== 'mouse'; });
  grid.addEventListener('pointerup', () => { touchPet = false; });
  grid.addEventListener('pointermove', (e) => {
    if (e.pointerType !== 'mouse' && !touchPet) return;
    const hole = e.target.closest('.niche.is-opened .niche__hole');
    if (hole) pet(hole.closest('.niche'), e.clientX, e.clientY);
  });

  // ---------- Fenêtre de la case ----------
  let current = 0;
  const showModal = (n) => {
    const jour = data.jours[n - 1];
    current = n;
    const media = jour.image
      ? `<img src="${esc(jour.image)}" alt="${esc(jour.titre)}">`
      : `<span class="avent-card__emoji" aria-hidden="true">${esc(jour.emoji)}</span>`;
    modal.querySelector('.avent-card__media').innerHTML = `${media}<span class="avent-card__day"><small>Niche</small>${n}<small>décembre</small></span>`;
    modal.querySelector('.avent-card__media').className = `avent-card__media avent-card__media--${n % 6}${jour.image ? ' has-img' : ''}`;
    modal.querySelector('.avent-card__title').textContent = jour.titre;
    modal.querySelector('.avent-card__text').textContent = jour.texte;
    const cta = modal.querySelector('.avent-card__cta');
    if (jour.bouton) { cta.hidden = false; cta.textContent = jour.bouton.texte; cta.href = jour.bouton.lien; } else cta.hidden = true;
    modal.querySelector('[data-nav="prev"]').disabled = n <= 1;
    modal.querySelector('[data-nav="next"]').disabled = n >= openUntil;
    modal.hidden = false;
    modal.classList.remove('is-in'); void modal.offsetWidth; modal.classList.add('is-in');
    document.documentElement.classList.add('avent-lock');
    modal.querySelector('.avent-card__close').focus({ preventScroll: true });
  };
  const closeModal = () => {
    modal.hidden = true;
    document.documentElement.classList.remove('avent-lock');
    const btn = grid.querySelector(`[data-day="${current}"]`);
    if (btn) btn.focus({ preventScroll: true });
    if (params.has('jour')) history.replaceState(null, '', location.pathname + location.hash.replace(/^#jour-\d+/, ''));
  };

  const openNiche = (btn, instant) => {
    const n = +btn.dataset.day;
    if (n > openUntil) {
      const tip = btn.querySelector('.niche__tip');
      tip.textContent = openUntil === 0 ? 'Patience, on ouvre le 1er décembre !' : `Patience ! Ouverture le ${n} décembre 🐾`;
      btn.classList.remove('is-shaking'); void btn.offsetWidth; btn.classList.add('is-shaking', 'show-tip');
      clearTimeout(btn._t); btn._t = setTimeout(() => btn.classList.remove('show-tip'), 2200);
      return;
    }
    const already = btn.classList.contains('is-opened');
    btn.classList.remove('is-ready'); btn.classList.add('is-opened', 'is-opening');
    btn.querySelector('.niche__badge')?.remove();
    root.querySelector('.avent__go')?.remove();
    remember(n);
    if (already || instant || reduceMotion) { showModal(n); return; }
    setTimeout(() => burst(btn), 450);
    setTimeout(() => btn.classList.remove('is-opening'), 1400);
    setTimeout(() => showModal(n), 1350);
  };

  grid.addEventListener('click', (e) => {
    const btn = e.target.closest('.niche');
    if (btn) openNiche(btn);
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal || e.target.closest('.avent-card__close')) closeModal();
    const nav = e.target.closest('[data-nav]');
    if (nav && !nav.disabled) {
      const n = current + (nav.dataset.nav === 'next' ? 1 : -1);
      const btn = grid.querySelector(`[data-day="${n}"]`);
      btn.classList.remove('is-ready'); btn.classList.add('is-opened'); remember(n);
      showModal(n);
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
    const text = `Niche n°${current} du calendrier de l'avent de Coeur de Meute 🐾`;
    const done = modal.querySelector('.avent-card__share-done');
    if (navigator.share) { navigator.share({ title: 'Coeur de Meute', text, url }).catch(() => {}); return; }
    (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(() => {
      done.textContent = 'Lien copié !';
      setTimeout(() => { done.textContent = ''; }, 2500);
    }).catch(() => { done.textContent = url; });
  };

  // ---------- Lien direct depuis les réseaux sociaux : ?jour=5 ----------
  const wanted = parseInt(params.get('jour') || (location.hash.match(/^#jour-(\d+)/) || [])[1], 10);
  if (wanted >= 1 && wanted <= 24) {
    const btn = grid.querySelector(`[data-day="${wanted}"]`);
    root.scrollIntoView({ behavior: 'instant', block: 'start' });
    setTimeout(() => openNiche(btn), 700);
  }
})();
