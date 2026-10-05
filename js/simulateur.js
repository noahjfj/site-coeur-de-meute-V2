// Simulateur de prix de la pension canine
// Prix : ceux enregistrés depuis admin.html (API Netlify), sinon valeurs par défaut de js/tarifs.js
(function () {
  const sims = document.querySelectorAll('[data-simulator]');
  if (!window.TARIFS_PENSION) return;

  let tarifs = window.TARIFS_PENSION;

  const euros = (n) => n.toLocaleString('fr-BE', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const clampDays = (v) => Math.min(90, Math.max(1, Math.round(Number(v) || 1)));
  const pawSizes = [22, 28, 34, 38, 40, 42];

  function renderMinPrice() {
    const min = Math.min(...tarifs.gabarits.map((g) => Math.min(g.prix, g.prixLongSejour)));
    document.querySelectorAll('[data-tarif-min]').forEach((el) => { el.textContent = euros(min); });
  }

  function renderSim(sim, simIndex) {
    const seuil = tarifs.seuilJours;
    const sizes = sim.querySelector('[data-sim-sizes]');
    const checked = sim.querySelector('input[type=radio]:checked');
    const selected = checked ? Math.min(Number(checked.value), tarifs.gabarits.length - 1) : 0;

    sizes.innerHTML = tarifs.gabarits.map((g, i) => `
      <label class="sim__size">
        <input type="radio" name="sim-gabarit-${simIndex}" value="${i}"${i === selected ? ' checked' : ''}>
        <span class="sim__size-card">
          <svg viewBox="0 0 48 48" style="width:${pawSizes[i]}px;height:${pawSizes[i]}px" aria-hidden="true"><path d="M24 24 C17 24 13 32 15 37 C17 41 21 39 24 39 C27 39 31 41 33 37 C35 32 31 24 24 24 Z" /><circle cx="16" cy="16" r="4" /><circle cx="32" cy="16" r="4" /><circle cx="10" cy="27" r="3.5" /><circle cx="38" cy="27" r="3.5" /></svg>
          <strong>${esc(g.nom)}</strong>
          <small>${esc(g.description)}</small>
          <em>${euros(g.prix)} € / jour</em>
        </span>
      </label>`).join('');

    const table = sim.parentElement.querySelector('[data-sim-table]');
    if (table) {
      table.innerHTML = `
        <thead><tr><th scope="col">Gabarit</th><th scope="col">1 à ${seuil} jours</th><th scope="col">Dès ${seuil + 1} jours</th></tr></thead>
        <tbody>${tarifs.gabarits.map((g) => `
          <tr><th scope="row">${esc(g.nom)}<small>${esc(g.description)}</small></th><td>${euros(g.prix)} € / jour</td><td>${euros(g.prixLongSejour)} € / jour</td></tr>`).join('')}
        </tbody>`;
    }

    update(sim);
  }

  function update(sim) {
    const seuil = tarifs.seuilJours;
    const days = sim.querySelector('[data-sim-days]');
    const range = sim.querySelector('[data-sim-range]');
    const saving = sim.querySelector('[data-sim-saving]');
    const g = tarifs.gabarits[Number(sim.querySelector('input[type=radio]:checked').value)];
    const n = clampDays(days.value);
    const long = n > seuil;
    const perDay = long ? g.prixLongSejour : g.prix;

    sim.querySelector('[data-sim-total]').textContent = euros(n * perDay);
    sim.querySelector('[data-sim-detail]').textContent = `${n} jour${n > 1 ? 's' : ''} × ${euros(perDay)} € — ${g.nom.toLowerCase()}`;

    if (long && g.prix > g.prixLongSejour) {
      saving.hidden = false;
      saving.textContent = `Tarif long séjour appliqué : vous économisez ${euros(n * (g.prix - g.prixLongSejour))} €`;
    } else {
      saving.hidden = true;
    }

    const reste = seuil + 1 - n;
    sim.querySelector('[data-sim-hint]').textContent = long
      ? `Séjour de plus de ${seuil} jours : tarif long séjour sur tout le séjour.`
      : `Encore ${reste} jour${reste > 1 ? 's' : ''} et le tarif long séjour s'applique : ${euros(g.prixLongSejour)} € / jour.`;

    const max = Number(range.max);
    const min = Number(range.min);
    range.value = Math.min(n, max);
    range.style.setProperty('--fill', `${((Math.min(n, max) - min) / (max - min)) * 100}%`);
  }

  sims.forEach((sim, simIndex) => {
    const days = sim.querySelector('[data-sim-days]');
    const range = sim.querySelector('[data-sim-range]');

    sim.querySelector('[data-sim-sizes]').addEventListener('change', () => update(sim));
    days.addEventListener('input', () => update(sim));
    days.addEventListener('change', () => { days.value = clampDays(days.value); update(sim); });
    range.addEventListener('input', () => { days.value = range.value; update(sim); });
    sim.querySelector('[data-sim-minus]').addEventListener('click', () => { days.value = Math.max(1, clampDays(days.value) - 1); update(sim); });
    sim.querySelector('[data-sim-plus]').addEventListener('click', () => { days.value = clampDays(Number(days.value) + 1); update(sim); });

    renderSim(sim, simIndex);
  });
  renderMinPrice();

  // Tarifs à jour depuis l'administration (uniquement quand le site est hébergé sur Netlify)
  if (location.protocol.startsWith('http')) {
    fetch('/api/tarifs', { cache: 'no-store' })
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (!data || !data.tarifs || !Array.isArray(data.tarifs.gabarits) || !data.tarifs.gabarits.length) return;
        tarifs = data.tarifs;
        sims.forEach(renderSim);
        renderMinPrice();
      })
      .catch(() => { /* hors Netlify : on garde les tarifs par défaut */ });
  }
})();
