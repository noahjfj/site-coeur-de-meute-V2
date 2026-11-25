// Espace client Coeur de Meute — petites aides d'interface (le site marche aussi sans JavaScript)
(function () {
  document.documentElement.classList.add('js');

  // Confirmation avant une action importante (supprimer, annuler…)
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Sur téléphone, chaque ligne de tableau devient une petite fiche : on recopie le titre de colonne
  document.querySelectorAll('table.table').forEach(function (table) {
    var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return th.querySelector('.sr-only') ? '' : th.textContent.trim(); });
    table.classList.add('table--stack');
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (cell, i) {
        if (heads[i]) cell.setAttribute('data-label', heads[i]);
        // Case vide (« — ») : inutile de l'afficher dans la fiche
        if (/^[—-]?$/.test(cell.textContent.trim()) && !cell.querySelector('input, select, button, a')) cell.classList.add('is-empty');
      });
    });
  });

  // Menu « 3 lignes » du téléphone : se ferme avec la croix, la touche Échap ou en choisissant une page
  document.querySelectorAll('[data-drawer]').forEach(function (drawer) {
    var close = function () { drawer.open = false; };
    drawer.querySelectorAll('[data-drawer-close], .drawer__link').forEach(function (el) { el.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && drawer.open) { close(); drawer.querySelector('summary').focus(); } });
    drawer.addEventListener('toggle', function () {
      document.documentElement.classList.toggle('menu-open', drawer.open);
      drawer.querySelector('summary').setAttribute('aria-label', drawer.open ? 'Fermer le menu' : 'Ouvrir le menu');
    });
  });

  // Sur téléphone, les filtres de l'historique sont repliés (sauf s'il y a déjà un filtre actif)
  document.querySelectorAll('[data-filterbox]').forEach(function (box) {
    if (window.matchMedia('(max-width: 760px)').matches && !box.hasAttribute('data-active')) box.open = false;
  });

  // Nouvelle réservation (gérante) : la date de départ et le paiement ne servent que pour un séjour
  document.querySelectorAll('[data-booking]').forEach(function (f) {
    var sync = function () {
      var journee = (f.querySelector('input[name=kind]:checked') || {}).value === 'journee';
      f.querySelectorAll('[data-only-sejour]').forEach(function (el) { el.hidden = journee; });
      f.querySelectorAll('[data-only-journee]').forEach(function (el) { el.hidden = !journee; });
      var lab = f.querySelector('[data-label-from]');
      if (lab) lab.textContent = journee ? 'Jour' : 'Arrivée';
    };
    f.addEventListener('change', sync);
    sync();
  });

  // Photo du chien : aperçu immédiat de la photo choisie
  document.querySelectorAll('[data-photopick]').forEach(function (box) {
    var input = box.querySelector('[data-photo-input]');
    var img = box.querySelector('[data-photo-preview]');
    var empty = box.querySelector('[data-photo-empty]');
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f || !/^image\//.test(f.type)) return;
      img.src = URL.createObjectURL(f);
      img.hidden = false;
      if (empty) empty.hidden = true;
    });
  });

  // Dépôt de contrat : on affiche le nom du fichier ou « Photo prête »
  document.querySelectorAll('[data-upload]').forEach(function (form) {
    var name = form.querySelector('[data-upload-name]');
    var inputs = form.querySelectorAll('[data-upload-input]');
    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        inputs.forEach(function (other) { if (other !== input) other.value = ''; });
        if (!name) return;
        name.hidden = !f;
        name.textContent = f ? '✓ Prêt à envoyer : ' + (input.name === 'photo' ? 'photo prise avec le téléphone' : f.name) : '';
      });
    });
  });

  // Planning : copier le lien de l'agenda
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var field = btn.parentNode.querySelector('[data-copy-src]');
      field.select();
      var done = function () { btn.textContent = 'Copié ✓'; setTimeout(function () { btn.textContent = 'Copier'; }, 2000); };
      if (navigator.clipboard) navigator.clipboard.writeText(field.value).then(done, function () { document.execCommand('copy'); done(); });
      else { document.execCommand('copy'); done(); }
    });
  });

  // Choix d'un client dans une longue liste : filtre en direct par nom du client ou du chien
  document.querySelectorAll('[data-picker]').forEach(function (picker) {
    var search = picker.querySelector('[data-picker-search]');
    var items = Array.prototype.slice.call(picker.querySelectorAll('[data-search]'));
    var none = picker.querySelector('[data-picker-none]');
    var sizeSelect = picker.form && picker.form.querySelector('select[name=size]');
    search.addEventListener('input', function () {
      var words = search.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
      var shown = 0;
      items.forEach(function (item) {
        var hay = item.getAttribute('data-search');
        var ok = words.every(function (w) { return hay.indexOf(w) !== -1; });
        item.hidden = !ok;
        if (ok) shown++;
      });
      if (none) none.hidden = shown > 0;
    });
    // Si tous les chiens du client ont le même gabarit, on le présélectionne
    picker.addEventListener('change', function (e) {
      var item = e.target.closest('[data-sizes]');
      var sizes = item ? item.getAttribute('data-sizes').split(' ').filter(Boolean) : [];
      if (sizeSelect && sizes.length === 1) {
        sizeSelect.value = sizes[0];
        sizeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  });

  // ---------- Demande de séjour ----------
  var form = document.getElementById('request-form');
  if (!form) return;

  var seuil = Number(form.getAttribute('data-seuil')) || 5;
  var prices = {};
  try { prices = JSON.parse(form.getAttribute('data-prices')) || {}; } catch (e) { prices = {}; }
  var cards = {};
  try { cards = JSON.parse(form.getAttribute('data-cards')) || {}; } catch (e) { cards = {}; }
  var sizeNames = { petit: 'petit chien', moyen: 'chien moyen', grand: 'grand chien' };
  var mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
  var summary = document.getElementById('summary-lines');

  function q(sel) { return form.querySelector(sel); }
  function qa(sel) { return Array.prototype.slice.call(form.querySelectorAll(sel)); }
  function fmt(iso) { var p = iso.split('-'); return Number(p[2]) + ' ' + mois[Number(p[1]) - 1]; }
  function days(a, b) { return Math.round((Date.parse(b) - Date.parse(a)) / 86400000); }
  function text(tag, cls, value) { var el = document.createElement(tag); if (cls) el.className = cls; el.textContent = value; return el; }

  function render() {
    var kind = (q('input[name=kind]:checked') || {}).value || 'sejour';
    var journee = kind === 'journee';
    var dogs = qa('input[name="dogs[]"]:checked');
    var multi = dogs.length > 1;
    var modeEl = q('input[name=mode]:checked');
    var diff = multi && modeEl && modeEl.value === 'diff';

    qa('[data-only-sejour]').forEach(function (el) { el.classList.toggle('is-hidden', journee); });
    qa('[data-label-from]').forEach(function (el) { el.textContent = journee ? 'Jour souhaité' : "Date d'arrivée"; });
    qa('[data-only-multi]').forEach(function (el) { el.classList.toggle('is-hidden', !multi); });
    var same = q('[data-dates=same]');
    if (same) same.classList.toggle('is-hidden', diff || dogs.length === 0);
    var chosenIds = dogs.map(function (d) { return d.value; });
    qa('[data-dates=diff]').forEach(function (el) {
      el.classList.toggle('is-hidden', !diff || chosenIds.indexOf(el.getAttribute('data-dog')) === -1);
    });

    // Récapitulatif
    summary.textContent = '';
    if (!dogs.length) { summary.appendChild(text('p', 'muted', 'Choisissez au moins un chien.')); return; }
    var total = 0, complete = true;
    var used = {};
    dogs.forEach(function (d) {
      var id = d.value;
      var from = diff ? (q('input[name="from[' + id + ']"]') || {}).value : (q('input[name=same_from]') || {}).value;
      var to = diff ? (q('input[name="to[' + id + ']"]') || {}).value : (q('input[name=same_to]') || {}).value;
      var line = document.createElement('div');
      line.className = 'summary__line';
      var left = document.createElement('div');
      left.appendChild(text('strong', '', d.getAttribute('data-name')));
      var right = '';
      if (!from || (!journee && !to)) {
        left.appendChild(text('span', '', 'Dates à choisir'));
        complete = false;
      } else if (journee) {
        var sz = d.getAttribute('data-size');
        used[sz] = (used[sz] || 0) + 1;
        if (used[sz] > (cards[sz] || 0)) {
          left.appendChild(text('span', 'summary__warn', 'Pas de journée disponible sur une carte « ' + sizeNames[sz] + ' »'));
          complete = false;
        } else {
          left.appendChild(text('span', '', 'Journée du ' + fmt(from) + ' · carte ' + sizeNames[sz]));
          right = '1 journée';
        }
      } else {
        var n = days(from, to);
        var p = prices[d.getAttribute('data-size')] || [0, 0];
        if (n < 1) { left.appendChild(text('span', '', 'Départ après l’arrivée, svp')); complete = false; }
        else {
          var rate = n > seuil ? p[1] : p[0];
          left.appendChild(text('span', '', fmt(from) + ' → ' + fmt(to) + ' · ' + n + ' jour' + (n > 1 ? 's' : '') + ' × ' + rate + ' €'));
          right = (n * rate) + ' €';
          total += n * rate;
        }
      }
      line.appendChild(left);
      line.appendChild(text('strong', '', right));
      summary.appendChild(line);
    });
    if (complete) {
      var t = document.createElement('div');
      t.className = 'summary__total';
      t.appendChild(text('span', '', journee ? 'Déduit de ma carte' : 'Estimation'));
      t.appendChild(text('span', '', journee ? dogs.length + ' journée' + (dogs.length > 1 ? 's' : '') : total + ' €'));
      summary.appendChild(t);
      var pct = Number(form.getAttribute('data-acompte')) || 0;
      if (!journee && pct > 0 && total > 0) {
        var a = document.createElement('div');
        a.className = 'summary__deposit';
        var dep = Math.round(total * pct) / 100;
        a.appendChild(text('span', '', 'Acompte après confirmation (' + pct + ' %)'));
        a.appendChild(text('strong', '', dep.toFixed(2).replace('.', ',').replace(',00', '') + ' €'));
        summary.appendChild(a);
      }
    }
  }

  form.addEventListener('change', render);
  form.addEventListener('input', render);
  render();
})();
