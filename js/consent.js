/*
 * Bannière de cookies du site public.
 * Google Tag Manager (statistiques) n'est chargé QUE si le visiteur a cliqué « Accepter ».
 * Le choix est gardé 6 mois dans le navigateur, puis la question est reposée.
 * Le lien « Cookies » en bas de page permet de changer d'avis à tout moment.
 */
(function () {
  'use strict';
  var GTM_ID = 'GTM-5DZTCMGG';
  var KEY = 'cdm-cookies';
  var MAX_AGE = 1000 * 60 * 60 * 24 * 182; // environ 6 mois

  function read() {
    try {
      var v = JSON.parse(localStorage.getItem(KEY) || 'null');
      if (v && (v.choix === 'oui' || v.choix === 'non') && Date.now() - v.date < MAX_AGE) {
        return v.choix;
      }
    } catch (e) { /* navigateur sans stockage : on redemande */ }
    return null;
  }

  function save(choix) {
    try { localStorage.setItem(KEY, JSON.stringify({ choix: choix, date: Date.now() })); } catch (e) { /* rien */ }
  }

  var loaded = false;
  function loadGtm() {
    if (loaded) { return; }
    loaded = true;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtm.js?id=' + GTM_ID;
    document.head.appendChild(s);
  }

  // Efface les cookies de statistiques déjà déposés (Google Analytics) quand on refuse
  function clearStatsCookies() {
    var host = location.hostname.replace(/^www\./, '');
    document.cookie.split(';').forEach(function (c) {
      var name = c.split('=')[0].trim();
      if (/^(_ga|_gid|_gat|_gcl)/.test(name)) {
        ['', host, '.' + host].forEach(function (d) {
          document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + (d ? '; domain=' + d : '');
        });
      }
    });
  }

  var banner = null;
  function show() {
    if (banner) { banner.hidden = false; banner.querySelector('button').focus(); return; }
    banner = document.createElement('div');
    banner.className = 'cookies';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-live', 'polite');
    banner.setAttribute('aria-label', 'Cookies');
    banner.innerHTML =
      '<p class="cookies__text"><strong>Cookies</strong> Nous aimerions mesurer les visites du site (statistiques Google) pour l\'améliorer. ' +
      'Rien n\'est activé sans votre accord. <a href="espace/confidentialite.php#cookies">En savoir plus</a></p>' +
      '<div class="cookies__actions">' +
      '<button type="button" class="cookies__btn cookies__btn--ghost" data-choix="non">Refuser</button>' +
      '<button type="button" class="cookies__btn" data-choix="oui">Accepter</button>' +
      '</div>';
    banner.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-choix]');
      if (!btn) { return; }
      var choix = btn.getAttribute('data-choix');
      var before = read();
      save(choix);
      banner.hidden = true;
      if (choix === 'oui') {
        loadGtm();
      } else {
        clearStatsCookies();
        if (before === 'oui' || loaded) { location.reload(); } // décharge les statistiques déjà lancées
      }
    });
    document.body.appendChild(banner);
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-cookies]');
    if (link) { e.preventDefault(); show(); }
  });

  var choix = read();
  if (choix === 'oui') {
    loadGtm();
  } else if (choix === null) {
    show();
  }
}());
