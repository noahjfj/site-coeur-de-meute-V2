// Carte journée en 3D : elle suit la souris (ou le doigt), un reflet glisse dessus, un clic la retourne.
(function () {
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-card3d]').forEach(function (stage) {
    var tilt = stage.querySelector('[data-tilt]');
    var card = stage.querySelector('[data-flip]');
    var glare = stage.querySelector('[data-glare]');
    var frame = null;

    function move(e) {
      if (reduce) return;
      var r = stage.getBoundingClientRect();
      var x = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
      var y = Math.min(1, Math.max(0, (e.clientY - r.top) / r.height));
      if (frame) cancelAnimationFrame(frame);
      frame = requestAnimationFrame(function () {
        stage.classList.add('is-hover');
        tilt.style.transform = 'rotateX(' + ((0.5 - y) * 22).toFixed(2) + 'deg) rotateY(' + ((x - 0.5) * 30).toFixed(2) + 'deg)';
        if (glare) {
          glare.style.setProperty('--gx', Math.round(x * 100) + '%');
          glare.style.setProperty('--gy', Math.round(y * 100) + '%');
        }
      });
    }

    function leave() {
      if (frame) cancelAnimationFrame(frame);
      stage.classList.remove('is-hover');
      tilt.style.transform = 'rotateX(0deg) rotateY(0deg)';
    }

    stage.addEventListener('pointermove', move);
    stage.addEventListener('pointerleave', leave);
    stage.addEventListener('pointercancel', leave);
    // Un clic n'importe où sur la carte (ou autour) la retourne
    stage.addEventListener('click', function (e) {
      if (e.target !== card && !card.contains(e.target) && e.target !== stage && !stage.contains(e.target)) return;
      card.classList.toggle('is-flipped');
    });
    // Le bouton reste utilisable au clavier (Entrée / Espace) sans déclencher deux fois
    card.addEventListener('click', function (e) { if (e.detail === 0) { e.stopPropagation(); card.classList.toggle('is-flipped'); } });
  });

  // Plusieurs cartes : des onglets pour passer de l'une à l'autre, avec une petite animation d'entrée
  var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-tab]'));
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var id = tab.getAttribute('data-tab');
      tabs.forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
      document.querySelectorAll('[data-panel]').forEach(function (panel) {
        var show = panel.getAttribute('data-panel') === id;
        panel.hidden = !show;
        panel.classList.remove('is-entering');
        if (show && !reduce) {
          void panel.offsetWidth;
          panel.classList.add('is-entering');
        }
        var c = panel.querySelector('[data-flip]');
        if (c) c.classList.remove('is-flipped');
      });
    });
  });
})();
