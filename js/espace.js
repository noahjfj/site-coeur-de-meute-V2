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
  document.querySelectorAll('table.table:not(.table--inputs)').forEach(function (table) {
    var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return th.querySelector('.sr-only') ? '' : th.textContent.trim(); });
    table.classList.add('table--stack');
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (cell, i) {
        if (heads[i]) cell.setAttribute('data-label', heads[i]);
      });
    });
  });

  // ---------- Demande de séjour ----------
  var form = document.getElementById('request-form');
  if (!form) return;

  var seuil = Number(form.getAttribute('data-seuil')) || 5;
  var prices = {};
  try { prices = JSON.parse(form.getAttribute('data-prices')) || {}; } catch (e) { prices = {}; }
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
        left.appendChild(text('span', '', 'Journée du ' + fmt(from)));
        right = '1 journée';
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
    }
  }

  form.addEventListener('change', render);
  form.addEventListener('input', render);
  render();
})();
