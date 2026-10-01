// Bannière : devient translucide quand on fait défiler la page
const header = document.getElementById('site-header');
const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 40);
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();

// Menu mobile
const toggle = document.querySelector('.nav-toggle');
const nav = document.getElementById('site-nav');
toggle.addEventListener('click', () => {
  const open = toggle.getAttribute('aria-expanded') === 'true';
  toggle.setAttribute('aria-expanded', String(!open));
  nav.classList.toggle('is-open', !open);
});

// Année du copyright
document.getElementById('year').textContent = new Date().getFullYear();

// Galerie : visionneuse plein écran
const lightbox = document.getElementById('lightbox');
if (lightbox) {
  const img = lightbox.querySelector('img');
  const close = () => { lightbox.hidden = true; img.src = ''; };

  document.querySelectorAll('.gallery__open').forEach((btn) => {
    btn.addEventListener('click', () => {
      img.src = btn.dataset.full;
      img.alt = btn.querySelector('img').alt;
      lightbox.hidden = false;
    });
  });

  lightbox.addEventListener('click', (e) => { if (e.target !== img) close(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
}
