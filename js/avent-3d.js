// =========================================================
//  Calendrier de l'avent — la grande niche en 3D (Three.js)
//  Une grande niche enneigée avec 24 volets, des chiens à caresser,
//  un sapin, des guirlandes, de la neige… Si la 3D n'est pas possible
//  sur l'appareil, les petites niches classiques (js/avent.js) s'affichent.
// =========================================================
(function () {
  const root = document.getElementById('avent');
  if (!root || root.hidden || !window.AVENT_API) return;

  // ---------- La 3D est-elle possible ? ----------
  const testCanvas = document.createElement('canvas');
  let gl = null;
  try { gl = testCanvas.getContext('webgl2') || testCanvas.getContext('webgl'); } catch (e) { /* pas de WebGL */ }
  if (!gl) return;
  root.classList.add('avent--3d');

  const stage = root.querySelector('.avent3d');
  const loader = stage.querySelector('.avent3d__loader');
  const fallback = () => { root.classList.remove('avent--3d'); };

  // On ne charge Three.js que lorsque le calendrier approche de l'écran
  let requested = false;
  const load = () => {
    if (requested) return; requested = true;
    if (window.THREE) { start(); return; }
    const s = document.createElement('script');
    s.src = stage.dataset.three;
    s.onload = () => { try { start(); } catch (e) { console.error(e); fallback(); } };
    s.onerror = fallback;
    document.head.appendChild(s);
  };
  if (window.AVENT_API.pending) load();
  else new IntersectionObserver((entries, obs) => { if (entries[0].isIntersecting) { obs.disconnect(); load(); } }, { rootMargin: '600px' }).observe(root);

  function start() {
    const T = window.THREE;
    const API = window.AVENT_API;
    const { data, openUntil, isToday, reduceMotion } = API;

    // ======================================================
    //  Rendu
    // ======================================================
    const renderer = new T.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    const mobile = window.matchMedia('(max-width: 700px)').matches;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mobile ? 1.75 : 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = T.PCFSoftShadowMap;
    renderer.toneMapping = T.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.15;
    renderer.outputColorSpace = T.SRGBColorSpace;
    const canvas = renderer.domElement;
    canvas.className = 'avent3d__canvas';
    canvas.setAttribute('aria-hidden', 'true');
    stage.prepend(canvas);

    const scene = new T.Scene();
    scene.fog = new T.Fog(0x2c3a4d, 22, 60);
    const camera = new T.PerspectiveCamera(38, 1, 0.1, 200);

    // ---------- Petits outils ----------
    const mat = (color, o = {}) => new T.MeshStandardMaterial({ color, roughness: .85, metalness: 0, ...o });
    const mesh = (geo, material, x = 0, y = 0, z = 0, shadow = true) => {
      const m = new T.Mesh(geo, material); m.position.set(x, y, z);
      m.castShadow = shadow; m.receiveShadow = true; return m;
    };
    const canvasTex = (w, h, draw, repeat) => {
      const c = document.createElement('canvas'); c.width = w; c.height = h;
      draw(c.getContext('2d'), w, h);
      const t = new T.CanvasTexture(c); t.colorSpace = T.SRGBColorSpace; t.anisotropy = 4;
      if (repeat) { t.wrapS = t.wrapT = T.RepeatWrapping; t.repeat.set(repeat[0], repeat[1]); }
      return t;
    };
    const planks = (base, line, vertical) => canvasTex(256, 256, (g, w, h) => {
      g.fillStyle = base; g.fillRect(0, 0, w, h);
      for (let i = 0; i < 8; i++) {
        const shade = (Math.random() - .5) * 18;
        g.fillStyle = `rgba(${shade > 0 ? '255,255,255' : '0,0,0'},${Math.abs(shade) / 160})`;
        if (vertical) g.fillRect(i * 32, 0, 32, h); else g.fillRect(0, i * 32, w, 32);
        g.fillStyle = line;
        if (vertical) g.fillRect(i * 32, 0, 2, h); else g.fillRect(0, i * 32, w, 2);
        for (let k = 0; k < 3; k++) { // veines du bois
          g.strokeStyle = 'rgba(0,0,0,.06)'; g.beginPath();
          const o = i * 32 + 6 + k * 8;
          if (vertical) { g.moveTo(o, 0); g.bezierCurveTo(o + 4, h * .3, o - 4, h * .6, o, h); } else { g.moveTo(0, o); g.bezierCurveTo(w * .3, o + 4, w * .6, o - 4, w, o); }
          g.stroke();
        }
      }
    }, [1, 1]);
    const roundSprite = (inner, outer) => canvasTex(64, 64, (g) => {
      const r = g.createRadialGradient(32, 32, 0, 32, 32, 32);
      r.addColorStop(0, inner); r.addColorStop(.45, inner); r.addColorStop(1, outer);
      g.fillStyle = r; g.fillRect(0, 0, 64, 64);
    });
    const emojiTex = (txt, size = 128, color) => canvasTex(size, size, (g, w, h) => {
      g.textAlign = 'center'; g.textBaseline = 'middle';
      g.font = `${size * .72}px "Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif`;
      if (color) { g.fillStyle = color; g.font = `bold ${size * .8}px Georgia, serif`; }
      g.fillText(txt, w / 2, h / 2 + size * .05);
    });
    const heartTex = canvasTex(128, 128, (g) => {
      g.fillStyle = '#ff5d7a'; g.shadowColor = 'rgba(255,93,122,.8)'; g.shadowBlur = 16;
      g.beginPath(); g.moveTo(64, 108);
      g.bezierCurveTo(10, 72, 14, 22, 44, 22); g.bezierCurveTo(56, 22, 62, 30, 64, 38);
      g.bezierCurveTo(66, 30, 72, 22, 84, 22); g.bezierCurveTo(114, 22, 118, 72, 64, 108); g.fill();
      g.fillStyle = 'rgba(255,255,255,.55)'; g.beginPath(); g.ellipse(44, 42, 9, 6, -.6, 0, Math.PI * 2); g.fill();
    });
    const pawTex = canvasTex(128, 128, (g) => {
      g.fillStyle = '#f4d6a0'; g.shadowColor = 'rgba(244,214,160,.9)'; g.shadowBlur = 14;
      g.beginPath(); g.ellipse(64, 80, 26, 22, 0, 0, Math.PI * 2); g.fill();
      [[34, 48], [54, 34], [76, 34], [96, 48]].forEach(([x, y]) => { g.beginPath(); g.ellipse(x, y, 10, 13, 0, 0, Math.PI * 2); g.fill(); });
    });
    const starTex = canvasTex(128, 128, (g) => {
      g.fillStyle = '#fff4c8'; g.shadowColor = '#ffd76a'; g.shadowBlur = 20; g.beginPath();
      for (let i = 0; i < 10; i++) { const r = i % 2 ? 20 : 50, a = -Math.PI / 2 + i * Math.PI / 5; g.lineTo(64 + Math.cos(a) * r, 64 + Math.sin(a) * r); }
      g.fill();
    });
    const zTex = emojiTex('Z', 128, '#ffffff');

    const tweens = [];
    const tween = (dur, update, ease = (k) => k, done) => tweens.push({ t: 0, dur, update, ease, done });
    const easeOutBack = (k) => 1 + 2.4 * Math.pow(k - 1, 3) + 1.4 * Math.pow(k - 1, 2);
    const easeInOut = (k) => (k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2);
    const easeOut = (k) => 1 - Math.pow(1 - k, 3);

    // ======================================================
    //  Lumières et ciel
    // ======================================================
    scene.add(new T.HemisphereLight(0xaac4e8, 0xf2f5fa, .75));
    const moonLight = new T.DirectionalLight(0xcfe0ff, 1.5);
    moonLight.position.set(9, 16, 10);
    moonLight.castShadow = true;
    moonLight.shadow.mapSize.set(mobile ? 1024 : 2048, mobile ? 1024 : 2048);
    Object.assign(moonLight.shadow.camera, { left: -13, right: 13, top: 13, bottom: -6, near: 1, far: 45 });
    moonLight.shadow.bias = -.0008; moonLight.shadow.normalBias = .02;
    scene.add(moonLight);
    const porch = new T.PointLight(0xffb469, 18, 14, 1.6); porch.position.set(0, 3.5, 6.5); scene.add(porch);

    const moon = new T.Sprite(new T.SpriteMaterial({ map: roundSprite('rgba(255,247,226,1)', 'rgba(244,214,160,0)'), fog: false, depthWrite: false }));
    moon.position.set(22, 20, -40); moon.scale.set(6, 6, 1); scene.add(moon);
    const moonGlow = new T.Sprite(new T.SpriteMaterial({ map: roundSprite('rgba(244,214,160,.35)', 'rgba(244,214,160,0)'), fog: false, depthWrite: false, blending: T.AdditiveBlending }));
    moonGlow.position.copy(moon.position); moonGlow.scale.set(26, 26, 1); scene.add(moonGlow);

    { // étoiles
      const n = 600, pos = new Float32Array(n * 3);
      for (let i = 0; i < n; i++) {
        const a = Math.random() * Math.PI - Math.PI, b = Math.random() * .9 + .08, r = 80;
        pos.set([Math.cos(a) * Math.cos(b) * r, Math.sin(b) * r, Math.sin(a) * Math.cos(b) * r - 10], i * 3);
      }
      const g = new T.BufferGeometry(); g.setAttribute('position', new T.BufferAttribute(pos, 3));
      scene.add(new T.Points(g, new T.PointsMaterial({ size: .35, map: roundSprite('#fff', 'rgba(255,255,255,0)'), transparent: true, depthWrite: false, fog: false })));
    }

    // ======================================================
    //  Le sol enneigé
    // ======================================================
    const snowMat = mat(0xf4f8fc, { roughness: 1 });
    const ground = mesh(new T.CircleGeometry(60, 64), snowMat, 0, 0, 0, false);
    ground.rotation.x = -Math.PI / 2; scene.add(ground);
    const piles = [[-4.6, 3.2, 1.2], [4.7, 3.4, 1.1], [-1.5, 3.6, .7], [7.5, -1, 1.8], [-8.5, 2, 1.6], [2.2, 7.5, .9], [-3.5, 7, .8], [9, 4, 1.4]];
    piles.forEach(([x, z, r]) => { const p = mesh(new T.SphereGeometry(r, 20, 12), snowMat, x, 0, z); p.scale.y = .35; scene.add(p); });

    // ======================================================
    //  La grande niche
    // ======================================================
    const W = 8, H = 5, D = 5, PEAK = 2.9;
    const kennel = new T.Group(); scene.add(kennel);
    const wallTex = planks('#a65a43', 'rgba(60,25,15,.35)');
    wallTex.repeat.set(2, 1.4);
    const wallMat = mat(0xffffff, { map: wallTex });
    const sideTex = wallTex.clone(); sideTex.needsUpdate = true; sideTex.repeat.set(1.3, 1.4);
    const body = mesh(new T.BoxGeometry(W, H, D), [mat(0xffffff, { map: sideTex }), mat(0xffffff, { map: sideTex }), wallMat, wallMat, wallMat, wallMat], 0, H / 2, 0);
    kennel.add(body);
    // socle
    kennel.add(mesh(new T.BoxGeometry(W + .3, .3, D + .3), mat(0x5c3a2c), 0, .15, 0));

    // pignons (avant et arrière)
    const gableShape = new T.Shape(); gableShape.moveTo(-W / 2, 0); gableShape.lineTo(W / 2, 0); gableShape.lineTo(0, PEAK); gableShape.closePath();
    const gableTex = planks('#b66a50', 'rgba(60,25,15,.35)'); gableTex.repeat.set(1 / 4, 1 / 4);
    [D / 2, -D / 2].forEach((z) => {
      const g = mesh(new T.ShapeGeometry(gableShape), mat(0xffffff, { map: gableTex, side: T.DoubleSide }), 0, H, z);
      kennel.add(g);
    });

    // toit + neige + glaçons
    const slope = Math.atan2(PEAK + .15, W / 2 + .6), roofLen = Math.hypot(W / 2 + .6, PEAK + .15) + .15;
    const roofTex = planks('#3a2a24', 'rgba(0,0,0,.4)', true); roofTex.repeat.set(3, 1);
    const roofMat = mat(0xffffff, { map: roofTex });
    [-1, 1].forEach((side) => {
      const piv = new T.Group(); piv.position.set(0, H + PEAK + .12, 0); piv.rotation.z = -side * slope; kennel.add(piv);
      const roof = mesh(new T.BoxGeometry(roofLen, .26, D + 1.2), roofMat, side * roofLen / 2, 0, 0); piv.add(roof);
      const snow = mesh(new T.BoxGeometry(roofLen - .1, .26, D + 1.1), snowMat, side * roofLen / 2, .24, 0); piv.add(snow);
      for (let i = 0; i < 7; i++) { // bourrelets de neige
        const b = mesh(new T.SphereGeometry(.28, 12, 8), snowMat, side * (roofLen - .1), .2, -D / 2 - .3 + i * (D + .6) / 6); b.scale.set(1, .7, 1.4); piv.add(b);
      }
      const ice = new T.MeshStandardMaterial({ color: 0xdff1ff, roughness: .1, metalness: .1, transparent: true, opacity: .85 });
      for (let i = 0; i < 14; i++) {
        const len = .25 + Math.random() * .55;
        const c = mesh(new T.ConeGeometry(.06, len, 6), ice, side * (roofLen - .05), -len / 2 - .1, -D / 2 - .4 + i * (D + .8) / 13, false);
        c.rotation.x = Math.PI; c.rotation.z = side * slope; piv.add(c);
      }
    });
    // faîtière
    const ridge = mesh(new T.CylinderGeometry(.2, .2, D + 1.3, 12), snowMat, 0, H + PEAK + .42, 0); ridge.rotation.x = Math.PI / 2; kennel.add(ridge);

    // cheminée qui fume
    const chimney = new T.Group(); chimney.position.set(2.2, H + 1.6, -1); kennel.add(chimney);
    const brick = canvasTex(128, 128, (g) => {
      g.fillStyle = '#8b3a2e'; g.fillRect(0, 0, 128, 128); g.fillStyle = '#d9c7b8';
      for (let y = 0; y < 128; y += 21) { g.fillRect(0, y, 128, 3); for (let x = (y / 21) % 2 ? 0 : 21; x < 128; x += 42) g.fillRect(x, y, 3, 21); }
    });
    chimney.add(mesh(new T.BoxGeometry(.8, 2.4, .8), mat(0xffffff, { map: brick })));
    chimney.add(mesh(new T.BoxGeometry(1, .22, 1), snowMat, 0, 1.25, 0));
    const smokeTex = roundSprite('rgba(220,226,235,.55)', 'rgba(220,226,235,0)');
    const smoke = [];
    for (let i = 0; i < 14; i++) {
      const s = new T.Sprite(new T.SpriteMaterial({ map: smokeTex, transparent: true, depthWrite: false }));
      s.userData.life = i / 14; scene.add(s); smoke.push(s);
    }
    const chimneyTop = new T.Vector3(); chimney.getWorldPosition(chimneyTop); chimneyTop.y += 1.4;

    // enseigne ronde avec couronne sur le pignon
    const sign = new T.Group(); sign.position.set(0, H + 1.15, D / 2 + .08); kennel.add(sign);
    const signTex = canvasTex(512, 512, (g) => {
      g.fillStyle = '#fffaf3'; g.beginPath(); g.arc(256, 256, 250, 0, Math.PI * 2); g.fill();
      g.fillStyle = '#2f3a4a'; g.textAlign = 'center';
      g.font = 'bold 74px Georgia, serif'; g.fillText('Coeur', 256, 205);
      g.font = 'italic 44px Georgia, serif'; g.fillStyle = '#a65a43'; g.fillText('de', 256, 262);
      g.font = 'bold 74px Georgia, serif'; g.fillStyle = '#2f3a4a'; g.fillText('Meute', 256, 335);
      g.font = '46px sans-serif'; g.fillText('🐾', 256, 410);
    });
    signTex.center.set(.5, .5); signTex.rotation = Math.PI / 2;
    const disc = mesh(new T.CylinderGeometry(.9, .9, .1, 48), [mat(0xe8dccb), mat(0xffffff, { map: signTex }), mat(0xe8dccb)]); disc.rotation.x = Math.PI / 2; sign.add(disc);
    const wreath = mesh(new T.TorusGeometry(1.02, .2, 12, 40), mat(0x2e6b47, { roughness: .7 }), 0, 0, .05); sign.add(wreath);
    for (let i = 0; i < 12; i++) {
      const a = i / 12 * Math.PI * 2;
      sign.add(mesh(new T.SphereGeometry(.09, 10, 8), mat(i % 3 ? 0xc0392b : 0xf4c95d, { roughness: .3, metalness: .3 }), Math.cos(a) * 1.02, Math.sin(a) * 1.02, .2));
    }
    const bow = mesh(new T.TorusKnotGeometry(.14, .05, 40, 6, 2, 3), mat(0xc0392b), 0, -1.12, .25); sign.add(bow);

    // guirlande lumineuse le long du toit (avant)
    const bulbs = [];
    const bulbColors = [0xff4d4d, 0xffd34d, 0x4dd2ff, 0x7dff6a, 0xff8de1];
    const bulbGeo = new T.SphereGeometry(.09, 10, 8);
    const eave = new T.Vector3(W / 2 + .55, H - .3, D / 2 + .62), top = new T.Vector3(0, H + PEAK + .05, D / 2 + .62);
    for (let side = -1; side <= 1; side += 2) {
      for (let i = 0; i <= 12; i++) {
        const k = i / 12;
        const p = new T.Vector3(side * eave.x * (1 - k), eave.y + (top.y - eave.y) * k - Math.sin(k * Math.PI * 6) * .08 - .25, eave.z);
        const m = new T.MeshBasicMaterial({ color: bulbColors[(i + (side > 0 ? 2 : 0)) % bulbColors.length] });
        const b = new T.Mesh(bulbGeo, m); b.position.copy(p); b.userData.base = m.color.clone(); b.userData.phase = Math.random() * 6;
        kennel.add(b); bulbs.push(b);
      }
    }

    // porte latérale (on y voit un chien)
    const sideDoorShape = new T.Shape(); sideDoorShape.moveTo(-.9, 0); sideDoorShape.lineTo(.9, 0); sideDoorShape.lineTo(.9, 1.6); sideDoorShape.absarc(0, 1.6, .9, 0, Math.PI, false); sideDoorShape.closePath();
    const sideDoor = mesh(new T.ShapeGeometry(sideDoorShape), new T.MeshBasicMaterial({ color: 0x140d0a }), W / 2 + .01, .3, .3, false);
    sideDoor.rotation.y = Math.PI / 2; kennel.add(sideDoor);

    // ======================================================
    //  Les 24 volets
    // ======================================================
    const shutters = [];
    const cols = 6, rows = 4, cw = 1.32, sw = .94, sh = .9;
    const numTex = (n, locked) => canvasTex(256, 256, (g, w, h) => {
      const special = n === 24;
      g.fillStyle = special ? '#f4d6a0' : '#f7efe4'; g.fillRect(0, 0, w, h);
      for (let x = 0; x < w; x += 32) { g.fillStyle = 'rgba(120,80,50,.12)'; g.fillRect(x, 0, 2, h); }
      g.strokeStyle = special ? '#b8433a' : '#a65a43'; g.lineWidth = 12; g.strokeRect(10, 10, w - 20, h - 20);
      g.fillStyle = special ? '#b8433a' : '#2f3a4a'; g.textAlign = 'center'; g.textBaseline = 'middle';
      g.font = 'bold 132px Georgia, serif'; g.fillText(n, w / 2, h / 2 - 6);
      if (special) { g.font = '48px sans-serif'; g.fillText('⭐', w / 2, 44); }
      if (locked) { // petit cadenas
        g.fillStyle = 'rgba(47,58,74,.55)'; g.fillRect(w / 2 - 16, h - 58, 32, 26);
        g.strokeStyle = 'rgba(47,58,74,.55)'; g.lineWidth = 6; g.beginPath(); g.arc(w / 2, h - 58, 11, Math.PI, 0); g.stroke();
      } else { g.font = '30px sans-serif'; g.fillText('🐾', w / 2, h - 44); }
      g.fillStyle = '#c9a24f'; g.beginPath(); g.arc(w - 46, h / 2 + 10, 9, 0, Math.PI * 2); g.fill();
    });
    const insideTex = (n) => canvasTex(256, 256, (g, w, h) => {
      const r = g.createRadialGradient(w / 2, h * .45, 10, w / 2, h / 2, w * .7);
      r.addColorStop(0, '#fff3c4'); r.addColorStop(.5, '#f6b25e'); r.addColorStop(1, '#a8552f');
      g.fillStyle = r; g.fillRect(0, 0, w, h);
      g.textAlign = 'center'; g.textBaseline = 'middle';
      g.font = '130px "Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif';
      g.fillText(data.jours[n - 1].emoji || '🎁', w / 2, h / 2 + 6);
    });
    const frameMat = mat(0x5c3a2c), sillMat = snowMat;
    for (let i = 0; i < 24; i++) {
      const n = i + 1, c = i % cols, r = Math.floor(i / cols);
      const x = -((cols - 1) * cw) / 2 + c * cw, y = H - .78 - r * 1.08;
      const g = new T.Group(); g.position.set(x, y, D / 2); kennel.add(g);
      const locked = n > openUntil;
      // cadre + rebord enneigé
      g.add(mesh(new T.BoxGeometry(sw + .16, .08, .14), frameMat, 0, sh / 2 + .04, .05));
      g.add(mesh(new T.BoxGeometry(sw + .16, .08, .14), frameMat, 0, -sh / 2 - .04, .05));
      g.add(mesh(new T.BoxGeometry(.08, sh + .16, .14), frameMat, -sw / 2 - .04, 0, .05));
      g.add(mesh(new T.BoxGeometry(.08, sh + .16, .14), frameMat, sw / 2 + .04, 0, .05));
      const sill = mesh(new T.BoxGeometry(sw + .3, .1, .3), sillMat, 0, -sh / 2 - .1, .12); g.add(sill);
      const cap = mesh(new T.CapsuleGeometry(.06, sw - .05, 4, 8), sillMat, 0, sh / 2 + .12, .07); cap.rotation.z = Math.PI / 2; g.add(cap);
      // intérieur (lumineux)
      const inMat = new T.MeshStandardMaterial({ map: insideTex(n), emissive: 0xffffff, emissiveMap: null, emissiveIntensity: 0, roughness: 1 });
      const inside = mesh(new T.PlaneGeometry(sw, sh), inMat, 0, 0, .012, false); g.add(inside);
      // volet (charnière à gauche)
      const hinge = new T.Group(); hinge.position.set(-sw / 2, 0, .05); g.add(hinge);
      const front = mat(0xffffff, { map: numTex(n, locked), emissive: 0xf4c070, emissiveIntensity: 0 });
      const edge = mat(0xd9c3a8);
      const panel = mesh(new T.BoxGeometry(sw, sh, .07), [edge, edge, edge, edge, front, mat(0xd8c3ad)], sw / 2, 0, 0);
      hinge.add(panel);
      inside.userData.day = n; panel.userData.day = n;
      g.traverse((m) => { if (m.isMesh) m.castShadow = false; });
      shutters.push({ n, group: g, hinge, panel, front, inside, inMat, open: 0, locked, wobble: 0 });
    }

    // ======================================================
    //  Décor : sapin, cadeaux, os, balle
    // ======================================================
    const tree = new T.Group(); tree.position.set(-7.4, 0, -.2); scene.add(tree);
    tree.add(mesh(new T.CylinderGeometry(.25, .32, 1, 10), mat(0x5c3a2c), 0, .5, 0));
    const needles = mat(0x1f5a3e, { roughness: .9, flatShading: true });
    [[2.4, 2.2, 1.5], [2, 2, 2.9], [1.55, 1.8, 4.2], [1.05, 1.5, 5.3]].forEach(([r, h, y]) => {
      tree.add(mesh(new T.ConeGeometry(r, h, 14), needles, 0, y, 0));
      const s = mesh(new T.ConeGeometry(r * .55, h * .35, 14), snowMat, 0, y + h * .28, 0); tree.add(s);
    });
    const treeStar = new T.Sprite(new T.SpriteMaterial({ map: starTex, depthWrite: false })); treeStar.position.set(0, 6.4, 0); treeStar.scale.set(1.3, 1.3, 1); tree.add(treeStar);
    const treeLights = [];
    for (let i = 0; i < 40; i++) {
      const k = Math.random(), y = 1 + k * 4.4, r = (1 - k) * 2.2 + .15, a = Math.random() * Math.PI * 2;
      const m = new T.MeshBasicMaterial({ color: bulbColors[i % bulbColors.length] });
      const b = new T.Mesh(new T.SphereGeometry(i % 4 ? .07 : .14, 10, 8), i % 4 ? m : mat(bulbColors[i % 5], { metalness: .5, roughness: .25 }));
      b.position.set(Math.cos(a) * r, y, Math.sin(a) * r); b.userData.base = m.color.clone(); b.userData.phase = Math.random() * 6;
      tree.add(b); if (i % 4) treeLights.push(b);
    }
    const treeLight = new T.PointLight(0xffd27a, 6, 8, 1.8); treeLight.position.set(-7.4, 4, 1.5); scene.add(treeLight);

    const gift = (x, z, s, color, ribbon, rot) => {
      const g = new T.Group(); g.position.set(x, s / 2, z); g.rotation.y = rot; scene.add(g);
      g.add(mesh(new T.BoxGeometry(s, s, s), mat(color, { roughness: .6 })));
      g.add(mesh(new T.BoxGeometry(s + .02, s + .02, s * .18), mat(ribbon, { roughness: .4 })));
      g.add(mesh(new T.BoxGeometry(s * .18, s + .02, s + .02), mat(ribbon, { roughness: .4 })));
      const k = mesh(new T.TorusGeometry(s * .15, s * .05, 8, 16), mat(ribbon), 0, s / 2 + s * .1, 0); g.add(k);
      return g;
    };
    gift(-5.6, 2.1, .8, 0xa65a43, 0xf4d6a0, .3); gift(-6.3, 3, .6, 0x2f3a4a, 0xc0392b, -.4); gift(-8.6, 2, .7, 0xf4d6a0, 0xa65a43, .8);
    gift(5.3, 3.4, .55, 0x8ea2b5, 0xffffff, .5);
    const ball = mesh(new T.SphereGeometry(.22, 16, 12), mat(0xd8382f, { roughness: .5 }), 6.6, .22, 3.3); scene.add(ball);
    const bone = new T.Group(); bone.position.set(1.6, .1, 5.6); bone.rotation.y = .6; scene.add(bone);
    const shaft = mesh(new T.CylinderGeometry(.06, .06, .6, 8), mat(0xf7efe4)); shaft.rotation.z = Math.PI / 2; bone.add(shaft);
    [-.3, .3].forEach((x) => [-.07, .07].forEach((z) => bone.add(mesh(new T.SphereGeometry(.09, 8, 6), mat(0xf7efe4), x, 0, z))));
    // gamelle
    const bowl = mesh(new T.CylinderGeometry(.42, .3, .25, 24, 1, true), mat(0xa65a43, { side: T.DoubleSide, roughness: .4 }), -1.1, .13, 5.3); scene.add(bowl);
    const kibble = mesh(new T.CircleGeometry(.36, 20), mat(0x8b5a3c), -1.1, .2, 5.3); kibble.rotation.x = -Math.PI / 2; scene.add(kibble);

    // ======================================================
    //  Les chiens
    // ======================================================
    const dogs = [];
    const dogMeshes = [];
    const makeDog = (o) => {
      const fur = mat(o.fur, { roughness: .95 }), ear = mat(o.ear, { roughness: .95 }), muz = mat(o.snout, { roughness: .95 }), dark = mat(0x1d1714, { roughness: .3 });
      const root3 = new T.Group(); root3.position.set(o.x, 0, o.z); root3.rotation.y = o.ry; root3.scale.setScalar(o.size || 1); scene.add(root3);
      const bodyG = new T.Group(); root3.add(bodyG);
      const dog = { name: o.name, root: root3, bodyG, pose: o.pose, happy: 0, petting: 0, jump: 0, sleep: !!o.sleep, wakeTimer: 0, nextHeart: 0, lookYaw: 0, lookPitch: 0, phase: Math.random() * 6, hopTimer: o.hops ? 2 : 0 };
      let headY, headX;
      if (o.pose === 'lie') {
        const b = mesh(new T.CapsuleGeometry(.32, .7, 8, 16), fur, 0, .34, 0); b.rotation.z = Math.PI / 2; bodyG.add(b);
        [[.48, .13], [.48, -.13]].forEach(([x, z]) => { const l = mesh(new T.CapsuleGeometry(.09, .3, 4, 8), fur, x + .12, .1, z * 1.6); l.rotation.z = Math.PI / 2; bodyG.add(l); });
        [[-.3, .26], [-.3, -.26]].forEach(([x, z]) => { const l = mesh(new T.SphereGeometry(.2, 12, 10), fur, x, .2, z); l.scale.set(1.3, .8, .7); bodyG.add(l); });
        headX = .62; headY = .62;
      } else if (o.pose === 'sit') {
        const b = mesh(new T.CapsuleGeometry(.3, .5, 8, 16), fur, -.05, .62, 0); b.rotation.z = Math.PI / 2 - .85; bodyG.add(b);
        const chest = mesh(new T.SphereGeometry(.27, 14, 12), o.chest ? muz : fur, .2, .78, 0); bodyG.add(chest);
        [[.24, .12], [.24, -.12]].forEach(([x, z]) => bodyG.add(mesh(new T.CapsuleGeometry(.08, .45, 4, 8), fur, x, .3, z)));
        [[.27, .12], [.27, -.12]].forEach(([x, z]) => { const p = mesh(new T.SphereGeometry(.1, 10, 8), fur, x + .06, .05, z); p.scale.set(1.3, .6, 1); bodyG.add(p); });
        [[-.22, .25], [-.22, -.25]].forEach(([x, z]) => { const l = mesh(new T.SphereGeometry(.24, 12, 10), fur, x, .24, z); l.scale.set(1.2, .9, .6); bodyG.add(l); });
        headX = .32; headY = 1.18;
      } else {
        const b = mesh(new T.CapsuleGeometry(.3, .6, 8, 16), fur, 0, .74, 0); b.rotation.z = Math.PI / 2; bodyG.add(b);
        const chest = mesh(new T.SphereGeometry(.28, 14, 12), o.chest ? muz : fur, .36, .8, 0); bodyG.add(chest);
        [[.34, .14], [.34, -.14], [-.34, .14], [-.34, -.14]].forEach(([x, z]) => bodyG.add(mesh(new T.CapsuleGeometry(.085, .42, 4, 8), fur, x, .3, z)));
        headX = .62; headY = 1.12;
      }
      // tête
      const head = new T.Group(); head.position.set(headX, headY, 0); bodyG.add(head); dog.head = head;
      const skull = mesh(new T.SphereGeometry(.31, 20, 16), fur); skull.scale.set(1, .95, .95); head.add(skull);
      const snout = mesh(new T.CapsuleGeometry(.13, .14, 6, 12), muz, .27, -.08, 0); snout.rotation.z = Math.PI / 2; head.add(snout);
      head.add(mesh(new T.SphereGeometry(.075, 12, 10), dark, .43, -.03, 0));
      const eyes = new T.Group(); head.add(eyes); dog.eyes = eyes;
      [.12, -.12].forEach((z) => {
        const e = mesh(new T.SphereGeometry(.05, 12, 10), dark, .23, .08, z, false); eyes.add(e);
        const hl = new T.Mesh(new T.SphereGeometry(.016, 6, 6), new T.MeshBasicMaterial({ color: 0xffffff })); hl.position.set(.27, .1, z + (z > 0 ? -.01 : .01)); eyes.add(hl);
      });
      // sourcils/joues roses quand il est content
      const blush = new T.MeshBasicMaterial({ color: 0xff8fa3, transparent: true, opacity: 0 }); dog.blush = blush;
      [.17, -.17].forEach((z) => { const b = new T.Mesh(new T.CircleGeometry(.05, 12), blush); b.position.set(.25, -.03, z * 1.05); b.lookAt(b.position.clone().multiplyScalar(3)); head.add(b); });
      const tongue = mesh(new T.CapsuleGeometry(.05, .08, 4, 8), mat(0xe57b7b), .36, -.2, 0, false); tongue.scale.setScalar(.01); head.add(tongue); dog.tongue = tongue;
      // oreilles
      dog.ears = [.25, -.25].map((z) => {
        const p = new T.Group(); p.position.set(-.02, .16, z); head.add(p);
        const e = mesh(new T.SphereGeometry(.1, 12, 10), ear, 0, o.pointy ? .12 : -.14, 0);
        e.scale.set(o.pointy ? .7 : .9, o.pointy ? 1.7 : 2.1, .55); p.add(e);
        p.rotation.x = (z > 0 ? 1 : -1) * (o.pointy ? -.2 : .35);
        p.userData.base = p.rotation.x; return p;
      });
      // queue
      const tail = new T.Group();
      if (o.pose === 'lie') tail.position.set(-.62, .4, 0); else if (o.pose === 'sit') tail.position.set(-.42, .2, .1); else tail.position.set(-.55, .9, 0);
      const tm = mesh(new T.CapsuleGeometry(.06, .38, 4, 8), fur, -.12, .17, 0); tm.rotation.z = .75; tail.add(tm);
      bodyG.add(tail); dog.tail = tail;
      if (o.pose === 'sit') tail.rotation.z = -1.1;
      // collier
      const collar = mesh(new T.TorusGeometry(.2, .045, 8, 20), mat(o.collar || 0xc0392b, { roughness: .4 }), headX - .2, headY - .27, 0);
      collar.rotation.y = Math.PI / 2; collar.rotation.x = o.pose === 'sit' ? 0 : .5; bodyG.add(collar);
      bodyG.add(mesh(new T.SphereGeometry(.055, 10, 8), mat(0xf4c95d, { metalness: .7, roughness: .25 }), headX - .02, headY - .42, 0));
      if (o.hat) { // bonnet de Noël
        const hat = new T.Group(); hat.position.set(-.02, .25, 0); hat.rotation.z = -.35; head.add(hat);
        hat.add(mesh(new T.ConeGeometry(.24, .5, 16), mat(0xc0392b), 0, .25, 0));
        const brim = mesh(new T.TorusGeometry(.22, .06, 8, 20), mat(0xffffff), 0, .02, 0); brim.rotation.x = Math.PI / 2; hat.add(brim);
        hat.add(mesh(new T.SphereGeometry(.08, 10, 8), mat(0xffffff), 0, .52, 0));
      }
      if (o.scarf) { // écharpe
        const sc = mesh(new T.TorusGeometry(.22, .08, 8, 20), mat(0x2e6b47), headX - .2, headY - .27, 0); sc.rotation.y = Math.PI / 2; sc.rotation.x = .3; bodyG.add(sc);
        const end = mesh(new T.BoxGeometry(.12, .4, .05), mat(0x2e6b47), headX - .08, headY - .5, .14); end.rotation.z = .2; bodyG.add(end);
      }
      if (o.sleep) { // coussin
        const cushion = mesh(new T.CylinderGeometry(1, 1.05, .16, 28), mat(0x8ea2b5, { roughness: 1 }), 0, .08, 0); root3.add(cushion);
        const zz = new T.Sprite(new T.SpriteMaterial({ map: zTex, transparent: true, depthWrite: false })); zz.scale.set(.35, .35, 1); scene.add(zz); dog.zz = zz;
      }
      root3.traverse((m) => { if (m.isMesh) { m.userData.dog = dog; dogMeshes.push(m); } });
      // zone de caresse un peu plus large que le chien (invisible)
      const hit = new T.Mesh(new T.SphereGeometry(o.pose === 'lie' ? .95 : .85, 10, 8), new T.MeshBasicMaterial({ visible: false }));
      hit.position.set(.1, .6, 0); hit.userData.dog = dog; root3.add(hit); dogMeshes.push(hit);
      dogs.push(dog);
      return dog;
    };

    makeDog({ name: 'Nala', x: 4.7, z: 4.4, ry: -1.9, pose: 'sit', fur: 0xc98a62, ear: 0x8b5a3c, snout: 0xf2dcc6, chest: true, hat: true, size: 1.05 });
    makeDog({ name: 'Rocky', x: -3, z: 4.3, ry: -.9, pose: 'lie', fur: 0x3d3a39, ear: 0x22201f, snout: 0x8b7b6e, sleep: true, collar: 0x2e6b47, size: 1.05 });
    makeDog({ name: 'Pépite', x: 6.4, z: 1.6, ry: -2.5, pose: 'stand', fur: 0xf4f0ea, ear: 0xc9a98a, snout: 0xffffff, pointy: true, collar: 0x2f6fb0 });
    makeDog({ name: 'Oslo', x: -5.2, z: 4.1, ry: -1.3, pose: 'sit', fur: 0xd9b98f, ear: 0xa8805c, snout: 0xf6e8d4, chest: true, scarf: true });
    makeDog({ name: 'Biscuit', x: .4, z: 5.9, ry: -1.6, pose: 'stand', fur: 0x8b6a52, ear: 0x5c4432, snout: 0xd8b99c, size: .72, hops: true, collar: 0xf4c95d });

    // ======================================================
    //  Neige qui tombe
    // ======================================================
    const flakes = reduceMotion ? 0 : (mobile ? 700 : 1500);
    let snowPts = null, snowPos = null, snowVel = null;
    if (flakes) {
      snowPos = new Float32Array(flakes * 3); snowVel = new Float32Array(flakes);
      for (let i = 0; i < flakes; i++) { snowPos.set([(Math.random() - .5) * 34, Math.random() * 16, (Math.random() - .5) * 24 + 2], i * 3); snowVel[i] = .6 + Math.random() * .9; }
      const g = new T.BufferGeometry(); g.setAttribute('position', new T.BufferAttribute(snowPos, 3));
      snowPts = new T.Points(g, new T.PointsMaterial({ size: .11, map: roundSprite('#ffffff', 'rgba(255,255,255,0)'), transparent: true, depthWrite: false }));
      scene.add(snowPts);
    }

    // ======================================================
    //  Particules : coeurs, pattes, étoiles
    // ======================================================
    const parts = [];
    const spawn = (tex, pos, vel, life, size, gravity = -2.5) => {
      const s = new T.Sprite(new T.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
      s.position.copy(pos); s.scale.set(size, size, 1); scene.add(s);
      parts.push({ s, vel, life, max: life, size, gravity });
    };

    // ======================================================
    //  Bulles et étiquettes HTML au-dessus de la 3D
    // ======================================================
    const overlay = stage.querySelector('.avent3d__overlay');
    const tags = [];
    const addTag = (cls, html, anchor, ttl) => {
      const el = document.createElement('span'); el.className = cls; el.innerHTML = html; overlay.appendChild(el);
      const t = { el, anchor, ttl, age: 0 }; tags.push(t); return t;
    };
    const removeTag = (t) => { t.el.remove(); tags.splice(tags.indexOf(t), 1); };
    const todayShutter = shutters.find((s) => isToday(s.n) && !API.isOpened(s.n));
    let todayTag = todayShutter ? addTag('avent3d__badge', 'Ouvre-moi&nbsp;!', () => todayShutter.group.localToWorld(new T.Vector3(0, .62, .1)), 0) : null;

    // ======================================================
    //  Caméra (cadrage selon la taille de l'écran)
    // ======================================================
    const focus = new T.Vector3(0, 3.3, 1);
    const camHome = new T.Vector3(), camTarget = new T.Vector3(), lookTarget = focus.clone(), lookNow = focus.clone();
    let zoomed = null; // volet sur lequel la caméra a zoomé
    const frame = () => {
      const w = stage.clientWidth, h = stage.clientHeight;
      renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix();
      const fitW = w < 640 ? 11.2 : 19.5, fitH = 11.2;
      const vFov = T.MathUtils.degToRad(camera.fov);
      const distW = (fitW / 2) / (Math.tan(vFov / 2) * camera.aspect), distH = (fitH / 2) / Math.tan(vFov / 2);
      const dist = Math.max(distW, distH);
      camHome.set(dist * .18, 3.6 + dist * .12, 1 + dist);
      if (!zoomed) camTarget.copy(camHome);
    };
    frame(); camera.position.copy(camHome).add(new T.Vector3(0, 6, 8)); // petite entrée en douceur
    camera.lookAt(focus);
    new ResizeObserver(frame).observe(stage);

    // ======================================================
    //  Son (désactivé par défaut)
    // ======================================================
    let audio = null, soundOn = false;
    const soundBtn = stage.querySelector('.avent3d__sound');
    soundBtn.addEventListener('click', () => {
      soundOn = !soundOn; soundBtn.setAttribute('aria-pressed', String(soundOn));
      soundBtn.textContent = soundOn ? '🔊 Son activé' : '🔇 Son coupé';
      if (soundOn && !audio) audio = new (window.AudioContext || window.webkitAudioContext)();
      if (soundOn) chime();
    });
    const tone = (f, t0, dur, type = 'sine', vol = .15, f2) => {
      const o = audio.createOscillator(), g = audio.createGain();
      o.type = type; o.frequency.setValueAtTime(f, audio.currentTime + t0);
      if (f2) o.frequency.exponentialRampToValueAtTime(f2, audio.currentTime + t0 + dur);
      g.gain.setValueAtTime(0, audio.currentTime + t0); g.gain.linearRampToValueAtTime(vol, audio.currentTime + t0 + .01);
      g.gain.exponentialRampToValueAtTime(.0001, audio.currentTime + t0 + dur);
      o.connect(g).connect(audio.destination); o.start(audio.currentTime + t0); o.stop(audio.currentTime + t0 + dur + .05);
    };
    const chime = () => { if (!soundOn) return; [1318, 1568, 2093, 2637].forEach((f, i) => tone(f, i * .09, .5, 'triangle', .08)); };
    const pop = () => { if (soundOn) tone(660 + Math.random() * 300, 0, .12, 'sine', .07, 1200); };
    const bark = (small) => {
      if (!soundOn) return;
      const base = small ? 520 : 300;
      [0, .17].forEach((t) => { tone(base, t, .14, 'sawtooth', .07, base * .6); tone(base * 2, t, .1, 'square', .025, base * 1.1); });
    };

    // ======================================================
    //  Interaction
    // ======================================================
    const ray = new T.Raycaster(), ndc = new T.Vector2();
    const shutterMeshes = shutters.flatMap((s) => [s.panel, s.inside]);
    let pointerDown = null, hoverShutter = null, pointerWorld = new T.Vector3(0, 2, 8), petCount = 0, hasPointer = false;
    const counter = stage.querySelector('.avent3d__pets');
    const hint = stage.querySelector('.avent3d__hint');
    const pick = (e) => {
      const r = canvas.getBoundingClientRect();
      ndc.set(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1);
      ray.setFromCamera(ndc, camera);
      const p = new T.Vector3(); ray.ray.intersectPlane(new T.Plane(new T.Vector3(0, 0, 1), -5), p); if (p) pointerWorld.copy(p);
      const hitDog = ray.intersectObjects(dogMeshes, false)[0];
      const hitShutter = ray.intersectObjects(shutterMeshes, false)[0];
      if (hitDog && (!hitShutter || hitDog.distance < hitShutter.distance)) return { dog: hitDog.object.userData.dog, point: hitDog.point };
      if (hitShutter) return { shutter: shutters[hitShutter.object.userData.day - 1], point: hitShutter.point };
      return {};
    };

    const pet = (dog, point, strong) => {
      const now = performance.now();
      dog.petting = 1; dog.happy = Math.min(1, dog.happy + (strong ? .5 : .06));
      if (dog.sleep) { dog.sleep = false; dog.wakeTimer = 8; addTag('avent3d__bubble', `${dog.name} se réveille… 🥱`, () => dog.head.localToWorld(new T.Vector3(0, .6, 0)), 1.8); }
      if (now > dog.nextHeart) {
        dog.nextHeart = now + (strong ? 0 : 140);
        spawn(heartTex, point.clone().add(new T.Vector3(0, .2, 0)), new T.Vector3((Math.random() - .5) * .8, 1.4 + Math.random(), (Math.random() - .5) * .4), 1.3, .32 + Math.random() * .18, .4);
        petCount++; counter.innerHTML = `❤️ <strong>${petCount}</strong> caresse${petCount > 1 ? 's' : ''}`; counter.classList.add('is-visible');
        pop();
        if (hint) hint.classList.add('is-done');
        if (petCount % 25 === 0) { // récompense
          addTag('avent3d__bubble avent3d__bubble--big', `${dog.name} vous adore&nbsp;! 🥰`, () => dog.head.localToWorld(new T.Vector3(0, .7, 0)), 2.2);
          dog.jump = 1; bark(dog.root.scale.x < 1);
          for (let i = 0; i < 14; i++) spawn(heartTex, point.clone(), new T.Vector3((Math.random() - .5) * 4, 2 + Math.random() * 2, (Math.random() - .5) * 2), 1.6, .3, -1.5);
        }
      }
    };
    const PHRASES = ['Wouf&nbsp;!', 'Wouf wouf&nbsp;! 🐾', 'Encore&nbsp;! ❤️', 'Joyeux Noël&nbsp;! 🎄', 'Une caresse&nbsp;?', 'On joue&nbsp;? 🎾', 'Merci&nbsp;! 🥰'];
    const poke = (dog, point) => {
      dog.jump = 1; pet(dog, point, true); bark(dog.root.scale.x < 1);
      addTag('avent3d__bubble', PHRASES[Math.floor(Math.random() * PHRASES.length)], () => dog.head.localToWorld(new T.Vector3(0, .6, 0)), 1.6);
    };

    const showTip = (s) => {
      addTag('avent3d__bubble avent3d__bubble--tip', API.tipText(s.n), () => s.group.localToWorld(new T.Vector3(0, -.7, .2)), 2.2);
      s.wobble = 1;
      if (soundOn) tone(180, 0, .15, 'square', .05);
    };

    const openShutter = (s, instant) => {
      if (s.locked) { showTip(s); return; }
      const was = API.isOpened(s.n) || s.open > .5;
      API.remember(s.n);
      if (todayTag && s === todayShutter) { removeTag(todayTag); todayTag = null; }
      const show = () => API.showModal(s.n);
      if (instant || reduceMotion) { s.open = 1; applyShutter(s); syncChip(s.n); show(); return; }
      // zoom sur le volet
      const wp = s.group.localToWorld(new T.Vector3());
      zoomed = s;
      camTarget.copy(wp).add(new T.Vector3(1.2, .4, mobile ? 6.5 : 5.2));
      lookTarget.copy(wp);
      if (!was) {
        tween(1.1, (k) => { s.open = k; applyShutter(s); }, easeOutBack);
        chime();
        setTimeout(() => {
          for (let i = 0; i < 30; i++) {
            const tex = [pawTex, starTex, heartTex][i % 3];
            spawn(tex, wp.clone().add(new T.Vector3(0, 0, .3)), new T.Vector3((Math.random() - .5) * 5, Math.random() * 4 + 1, Math.random() * 3 + .5), 1.4 + Math.random() * .6, .22 + Math.random() * .22);
          }
          dogs.forEach((d) => { d.jump = 1; d.happy = Math.max(d.happy, .6); if (d.sleep) { d.sleep = false; d.wakeTimer = 8; } });
          bark(false);
        }, 380);
      }
      syncChip(s.n);
      setTimeout(show, was ? 800 : 1900);
    };
    const applyShutter = (s) => {
      s.hinge.rotation.y = -1.95 * s.open;
      s.inMat.emissiveIntensity = .55 * Math.min(1, s.open * 1.4);
      s.inMat.emissiveMap = s.inMat.map; s.inMat.needsUpdate = s.inMat.needsUpdate || s.open === 1;
    };
    shutters.forEach((s) => { if (!s.locked && API.isOpened(s.n)) { s.open = 1; applyShutter(s); } });

    root.addEventListener('avent:closed', () => { zoomed = null; camTarget.copy(camHome); lookTarget.copy(focus); });
    root.addEventListener('avent:seen', (e) => { const s = shutters[e.detail - 1]; if (s && s.open < 1 && !zoomed) { s.open = 1; applyShutter(s); } syncChip(e.detail); });
    root.addEventListener('avent:navigate', (e) => {
      const s = shutters[e.detail - 1]; if (!s) return;
      if (s.open < 1) tween(.9, (k) => { s.open = Math.max(s.open, k); applyShutter(s); }, easeOutBack);
      const wp = s.group.localToWorld(new T.Vector3()); zoomed = s;
      camTarget.copy(wp).add(new T.Vector3(1.2, .4, mobile ? 6.5 : 5.2)); lookTarget.copy(wp);
    });

    canvas.addEventListener('pointerdown', (e) => { pointerDown = { x: e.clientX, y: e.clientY, t: performance.now() }; });
    canvas.addEventListener('pointermove', (e) => {
      hasPointer = true;
      const hit = pick(e);
      const canPet = e.pointerType === 'mouse' || pointerDown;
      if (hit.dog && canPet && !API.isModalOpen()) pet(hit.dog, hit.point);
      hoverShutter = hit.shutter && !hit.shutter.locked ? hit.shutter : null;
      canvas.style.cursor = hit.dog ? 'grab' : hit.shutter ? (hit.shutter.locked ? 'not-allowed' : 'pointer') : 'default';
    });
    canvas.addEventListener('pointerleave', () => { hasPointer = false; hoverShutter = null; });
    canvas.addEventListener('pointerup', (e) => {
      if (!pointerDown) return;
      const moved = Math.hypot(e.clientX - pointerDown.x, e.clientY - pointerDown.y);
      pointerDown = null;
      if (moved > 10) return;
      const hit = pick(e);
      if (hit.dog) poke(hit.dog, hit.point);
      else if (hit.shutter) openShutter(hit.shutter);
    });
    canvas.addEventListener('pointercancel', () => { pointerDown = null; });

    // ---------- Pastilles 1 à 24 (accès rapide + clavier) ----------
    const chips = stage.parentElement.querySelector('.avent__chips');
    const chipEls = [];
    shutters.forEach((s) => {
      const b = document.createElement('button'); b.type = 'button'; b.className = 'avent__chip'; b.textContent = s.n;
      b.setAttribute('aria-label', s.locked ? `${s.n} décembre : pas encore ouvert` : `Ouvrir la case du ${s.n} décembre`);
      if (s.locked) b.classList.add('is-locked'); if (isToday(s.n)) b.classList.add('is-today');
      b.addEventListener('click', () => { if (s.locked) { b.classList.remove('is-shake'); void b.offsetWidth; b.classList.add('is-shake'); } openShutter(s); });
      chips.appendChild(b); chipEls.push(b);
    });
    const syncChip = (n) => { const b = chipEls[n - 1]; if (b) b.classList.add('is-opened'); };
    shutters.forEach((s) => { if (API.isOpened(s.n)) syncChip(s.n); });

    // ======================================================
    //  Boucle d'animation
    // ======================================================
    const clock = new T.Clock();
    let running = false, elapsed = 0;
    const v = new T.Vector3();
    const loop = () => {
      if (!running) return;
      requestAnimationFrame(loop);
      const dt = Math.min(clock.getDelta(), .05); elapsed += dt;

      // tweens
      for (let i = tweens.length - 1; i >= 0; i--) {
        const tw = tweens[i]; tw.t += dt; const k = Math.min(1, tw.t / tw.dur);
        tw.update(tw.ease(k)); if (k >= 1) { tweens.splice(i, 1); if (tw.done) tw.done(); }
      }

      // caméra : douce oscillation + suit un peu la souris
      const sway = reduceMotion || zoomed ? 0 : 1;
      const px = hasPointer ? ndc.x : Math.sin(elapsed * .25) * .5, py = hasPointer ? ndc.y : 0;
      v.copy(camTarget).add(new T.Vector3(px * 1.6 * sway, py * .7 * sway, 0));
      camera.position.lerp(v, 1 - Math.pow(.02, dt));
      lookNow.lerp(lookTarget, 1 - Math.pow(.02, dt));
      camera.lookAt(lookNow);

      // guirlandes qui clignotent
      bulbs.forEach((b, i) => { const on = .45 + .55 * (Math.sin(elapsed * 3 + b.userData.phase + (i % 3) * 2) > -.2 ? 1 : 0); b.material.color.copy(b.userData.base).multiplyScalar(on); });
      treeLights.forEach((b) => { b.material.color.copy(b.userData.base).multiplyScalar(.55 + .45 * Math.sin(elapsed * 2.2 + b.userData.phase)); });
      treeStar.material.rotation = Math.sin(elapsed) * .15; const st = 1.3 + Math.sin(elapsed * 3) * .1; treeStar.scale.set(st, st, 1);
      treeLight.intensity = 5 + Math.sin(elapsed * 2) * 1.2;

      // volet du jour qui brille + volet survolé
      shutters.forEach((s) => {
        const glow = s === todayShutter && s.open < .5 ? .35 + .3 * Math.sin(elapsed * 4) : 0;
        const hov = s === hoverShutter && s.open < .5 ? .25 : 0;
        s.front.emissiveIntensity = glow + hov;
        const target = (s === hoverShutter ? 1 : 0) * .08 + (s === todayShutter && s.open < .5 ? .05 + .05 * Math.sin(elapsed * 4) : 0);
        s.group.position.z += ((D / 2 + target) - s.group.position.z) * .2;
        if (s.wobble > 0) { s.wobble = Math.max(0, s.wobble - dt * 1.8); s.hinge.rotation.y = -1.95 * s.open + Math.sin(s.wobble * 40) * .12 * s.wobble; s.hinge.rotation.z = Math.sin(s.wobble * 30) * .02; }
      });

      // fumée
      smoke.forEach((s) => {
        s.userData.life += dt * .18; if (s.userData.life > 1) s.userData.life -= 1;
        const l = s.userData.life;
        s.position.set(chimneyTop.x + Math.sin(l * 6 + elapsed) * .3 + l * 1.4, chimneyTop.y + l * 4, chimneyTop.z);
        const sc = .5 + l * 2.2; s.scale.set(sc, sc, 1); s.material.opacity = (1 - l) * .7 * Math.min(1, l * 6);
      });

      // neige
      if (snowPts) {
        for (let i = 0; i < flakes; i++) {
          const j = i * 3;
          snowPos[j + 1] -= snowVel[i] * dt; snowPos[j] += Math.sin(elapsed + i) * dt * .25;
          if (snowPos[j + 1] < 0) { snowPos[j + 1] = 16; snowPos[j] = (Math.random() - .5) * 34; }
        }
        snowPts.geometry.attributes.position.needsUpdate = true;
      }

      // chiens
      dogs.forEach((d) => {
        const t = elapsed + d.phase;
        d.petting = Math.max(0, d.petting - dt * 2.5);
        d.happy = Math.max(0, d.happy - dt * .12);
        if (d.wakeTimer > 0) { d.wakeTimer -= dt; if (d.wakeTimer <= 0 && d.zz && d.happy < .2) d.sleep = true; }
        if (d.hopTimer) { d.hopTimer -= dt; if (d.hopTimer <= 0) { d.jump = 1; d.hopTimer = 3 + Math.random() * 3; } }
        // respiration
        const breath = 1 + Math.sin(t * (d.sleep ? 1.6 : 2.6)) * (d.sleep ? .03 : .018);
        d.bodyG.scale.set(1, breath, 1 + (breath - 1) * .5);
        // saut
        if (d.jump > 0) { d.jump = Math.max(0, d.jump - dt * 1.9); d.bodyG.position.y = Math.sin((1 - d.jump) * Math.PI) * .55; d.bodyG.rotation.z = Math.sin((1 - d.jump) * Math.PI * 2) * .12; }
        else { d.bodyG.position.y *= .8; d.bodyG.rotation.z = d.petting * Math.sin(t * 18) * .04; }
        // queue : plus il est content, plus elle remue vite
        const wagSpeed = d.sleep ? 0 : 6 + d.happy * 22, wagAmp = d.sleep ? 0 : .35 + d.happy * .45;
        d.tail.rotation.y = Math.sin(t * wagSpeed) * wagAmp;
        // tête : suit le pointeur, penche quand on le caresse
        if (!d.sleep) {
          const hp = d.head.getWorldPosition(v.clone());
          const dir = pointerWorld.clone().sub(hp);
          const localYaw = Math.atan2(-dir.z, dir.x) - d.root.rotation.y;
          const yaw = T.MathUtils.clamp(Math.atan2(Math.sin(localYaw), Math.cos(localYaw)), -.9, .9);
          d.lookYaw += (yaw - d.lookYaw) * .06;
          d.head.rotation.y = d.lookYaw; d.head.rotation.z = Math.sin(t * .8) * .06 + d.happy * .15; d.head.rotation.x = d.petting * Math.sin(t * 5) * .25;
        } else { d.head.rotation.set(0, .2, -.25); }
        // yeux : fermés s'il dort ou s'il est aux anges, sinon clignent
        const blink = (t % 4.2) < .12 ? .15 : 1;
        const closed = d.sleep || d.happy > .45 ? .18 : blink;
        d.eyes.scale.y += (closed - d.eyes.scale.y) * .3;
        d.blush.opacity += ((d.happy > .25 ? .8 : 0) - d.blush.opacity) * .1;
        const tg = d.happy > .2 && !d.sleep ? 1 : .01; d.tongue.scale.setScalar(d.tongue.scale.x + (tg - d.tongue.scale.x) * .2);
        d.ears.forEach((e, i) => { e.rotation.x = e.userData.base + (i ? -1 : 1) * (d.petting * Math.sin(t * 14) * .25 + Math.sin(t * 1.3) * .04); });
        if (d.zz) {
          d.zz.visible = d.sleep;
          const l = (elapsed * .5) % 1; d.head.getWorldPosition(d.zz.position); d.zz.position.add(new T.Vector3(.2 + l * .5, .4 + l * 1.2, .1));
          d.zz.material.opacity = Math.sin(l * Math.PI); const zs = .25 + l * .3; d.zz.scale.set(zs, zs, 1);
        }
      });

      // particules
      for (let i = parts.length - 1; i >= 0; i--) {
        const p = parts[i]; p.life -= dt;
        if (p.life <= 0) { scene.remove(p.s); p.s.material.dispose(); parts.splice(i, 1); continue; }
        p.vel.y += p.gravity * dt; p.s.position.addScaledVector(p.vel, dt);
        const k = p.life / p.max; p.s.material.opacity = Math.min(1, k * 2.5); const sc = p.size * (1.2 - k * .2); p.s.scale.set(sc, sc, 1);
        p.s.material.rotation += dt * 2;
      }

      renderer.render(scene, camera);

      // étiquettes HTML
      const r = canvas.getBoundingClientRect();
      for (let i = tags.length - 1; i >= 0; i--) {
        const tg = tags[i];
        if (tg.ttl) { tg.age += dt; if (tg.age > tg.ttl) { removeTag(tg); continue; } }
        const p = tg.anchor().project(camera);
        const half = tg.el.offsetWidth / 2 + 8;
        const sx = Math.min(r.width - half, Math.max(half, (p.x * .5 + .5) * r.width));
        tg.el.style.transform = `translate(${sx}px, ${(-p.y * .5 + .5) * r.height}px) translate(-50%, -100%)`;
        tg.el.style.opacity = tg.ttl ? Math.min(1, (tg.ttl - tg.age) * 3) : 1;
      }
    };

    const visible = { on: false };
    new IntersectionObserver(([e]) => { visible.on = e.isIntersecting; toggle(); }).observe(stage);
    document.addEventListener('visibilitychange', () => toggle());
    function toggle() {
      const should = visible.on && !document.hidden;
      if (should && !running) { running = true; clock.getDelta(); loop(); } else if (!should) running = false;
    }

    // une première image puis on retire l'écran de chargement
    renderer.render(scene, camera);
    stage.classList.add('is-ready');
    setTimeout(() => loader.remove(), 900);
    const toScreen = (v) => { const r = canvas.getBoundingClientRect(); const p = v.project(camera); return { x: r.left + (p.x * .5 + .5) * r.width, y: r.top + (-p.y * .5 + .5) * r.height }; };
    window.AVENT_3D = { openDay: (n) => openShutter(shutters[n - 1]), shutters, dogs, pet, poke,
      screen: (i) => toScreen(dogs[i].head.getWorldPosition(new T.Vector3())), screenShutter: (n) => toScreen(shutters[n - 1].group.localToWorld(new T.Vector3())) };

    // lien direct depuis les réseaux sociaux
    if (API.pending) { const n = API.pending; API.pending = 0; setTimeout(() => openShutter(shutters[n - 1]), 1400); }
  }
})();
