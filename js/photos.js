// Photos du site : celles choisies dans l'espace administrateur (data/photos.json).
// Sans ce fichier (ou ouvert sans serveur), les photos d'origine restent affichées.
(function () {
  if (!location.protocol.startsWith('http')) return;

  const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  fetch('data/photos.json', { cache: 'no-store' })
    .then((res) => (res.ok ? res.json() : null))
    .then((data) => {
      if (!data) return;
      const slots = data.emplacements || {};

      // Photos simples : <img data-photo="clé">
      document.querySelectorAll('img[data-photo]').forEach((img) => {
        const p = slots[img.dataset.photo];
        if (p && p.src) {
          img.src = p.src;
          if (p.alt) img.alt = p.alt;
        }
      });

      // Visuels dessinés remplacés par une photo : <div data-photo-visual="clé">
      document.querySelectorAll('[data-photo-visual]').forEach((box) => {
        const p = slots[box.dataset.photoVisual];
        if (!p || !p.src) return;
        box.classList.add('has-photo');
        box.removeAttribute('aria-hidden');
        box.insertAdjacentHTML('afterbegin', `<img class="visual-photo" src="${esc(p.src)}" alt="${esc(p.alt)}" loading="lazy">`);
      });

      // Médaillons de l'équipe : <div data-photo-avatar="clé">
      document.querySelectorAll('[data-photo-avatar]').forEach((box) => {
        const p = slots[box.dataset.photoAvatar];
        if (!p || !p.src) return;
        box.innerHTML = `<img class="member__photo" src="${esc(p.src)}" alt="${esc(p.alt)}" loading="lazy">`;
        box.removeAttribute('aria-hidden');
      });

      // Galerie : <div data-gallery>
      const gallery = document.querySelector('[data-gallery]');
      if (gallery && Array.isArray(data.galerie)) {
        const colors = ['rust', 'sand', 'blue', 'brown'];
        const shape = (i) => (i % 9 === 0 || i % 9 === 5 ? ' gallery__item--wide' : (i % 9 === 2 ? ' gallery__item--tall' : ''));
        let html = data.galerie.map((p, i) => `
          <figure class="gallery__item${shape(i)}">
            <button type="button" class="gallery__open" data-full="${esc(p.src)}">
              <img src="${esc(p.src)}" alt="${esc(p.legende || 'Photo Coeur de Meute')}" loading="lazy">
            </button>
            ${p.legende ? `<figcaption>${esc(p.legende)}</figcaption>` : ''}
          </figure>`).join('');
        for (let i = data.galerie.length; i < 9; i++) {
          html += `<figure class="gallery__item gallery__placeholder gallery__placeholder--${colors[i % 4]}${shape(i)}"><span>Photo à venir</span></figure>`;
        }
        gallery.innerHTML = html;
      }
    })
    .catch(() => { /* fichier illisible : on garde les photos d'origine */ });
})();
