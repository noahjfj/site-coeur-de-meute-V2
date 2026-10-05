// Simulateur de prix de la pension canine (tarifs dans js/tarifs.js)
(function () {
  const tarifs = window.TARIFS_PENSION;
  if (!tarifs) return;

  const euros = (n) => n.toLocaleString('fr-BE', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  const seuil = tarifs.seuilJours;

  // Prix « à partir de » affichés ailleurs sur la page
  const minPrix = Math.min(...tarifs.gabarits.map((g) => Math.min(g.prix, g.prixLongSejour)));
  document.querySelectorAll('[data-tarif-min]').forEach((el) => { el.textContent = euros(minPrix); });

  document.querySelectorAll('[data-simulator]').forEach((sim, simIndex) => {
    const sizes = sim.querySelector('[data-sim-sizes]');
    const days = sim.querySelector('[data-sim-days]');
    const range = sim.querySelector('[data-sim-range]');
    const out = {
      total: sim.querySelector('[data-sim-total]'),
      detail: sim.querySelector('[data-sim-detail]'),
      saving: sim.querySelector('[data-sim-saving]'),
      hint: sim.querySelector('[data-sim-hint]'),
    };
    const pawSizes = [22, 28, 34];

    // Cartes de gabarit
    sizes.innerHTML = tarifs.gabarits.map((g, i) => `
      <label class="sim__size">
        <input type="radio" name="sim-gabarit-${simIndex}" value="${i}"${i === 0 ? ' checked' : ''}>
        <span class="sim__size-card">
          <svg viewBox="0 0 48 48" style="width:${pawSizes[i] || 28}px;height:${pawSizes[i] || 28}px" aria-hidden="true"><path d="M24 24 C17 24 13 32 15 37 C17 41 21 39 24 39 C27 39 31 41 33 37 C35 32 31 24 24 24 Z" /><circle cx="16" cy="16" r="4" /><circle cx="32" cy="16" r="4" /><circle cx="10" cy="27" r="3.5" /><circle cx="38" cy="27" r="3.5" /></svg>
          <strong>${g.nom}</strong>
          <small>${g.description}</small>
          <em>${euros(g.prix)} € / jour</em>
        </span>
      </label>`).join('');

    // Grille des tarifs
    const table = sim.parentElement.querySelector('[data-sim-table]');
    if (table) {
      table.innerHTML = `
        <thead><tr><th scope="col">Gabarit</th><th scope="col">1 à ${seuil} jours</th><th scope="col">Dès ${seuil + 1} jours</th></tr></thead>
        <tbody>${tarifs.gabarits.map((g) => `
          <tr><th scope="row">${g.nom}<small>${g.description}</small></th><td>${euros(g.prix)} € / jour</td><td>${euros(g.prixLongSejour)} € / jour</td></tr>`).join('')}
        </tbody>`;
    }

    const clampDays = (v) => Math.min(90, Math.max(1, Math.round(Number(v) || 1)));

    function update() {
      const g = tarifs.gabarits[Number(sim.querySelector('input[type=radio]:checked').value)];
      const n = clampDays(days.value);
      const long = n > seuil;
      const perDay = long ? g.prixLongSejour : g.prix;
      const total = n * perDay;

      out.total.textContent = euros(total);
      out.detail.textContent = `${n} jour${n > 1 ? 's' : ''} × ${euros(perDay)} € — ${g.nom.toLowerCase()}`;

      if (long && g.prix > g.prixLongSejour) {
        out.saving.hidden = false;
        out.saving.textContent = `Tarif long séjour appliqué : vous économisez ${euros(n * (g.prix - g.prixLongSejour))} €`;
      } else {
        out.saving.hidden = true;
      }

      if (!long) {
        const reste = seuil + 1 - n;
        out.hint.textContent = `Encore ${reste} jour${reste > 1 ? 's' : ''} et le tarif long séjour s'applique : ${euros(g.prixLongSejour)} € / jour.`;
      } else {
        out.hint.textContent = `Séjour de plus de ${seuil} jours : tarif long séjour sur tout le séjour.`;
      }

      range.value = Math.min(n, Number(range.max));
      range.style.setProperty('--fill', `${((Math.min(n, range.max) - range.min) / (range.max - range.min)) * 100}%`);
    }

    sizes.addEventListener('change', update);
    days.addEventListener('input', update);
    days.addEventListener('change', () => { days.value = clampDays(days.value); update(); });
    range.addEventListener('input', () => { days.value = range.value; update(); });
    sim.querySelector('[data-sim-minus]').addEventListener('click', () => { days.value = clampDays(days.value) - 1 || 1; days.dispatchEvent(new Event('change')); });
    sim.querySelector('[data-sim-plus]').addEventListener('click', () => { days.value = clampDays(Number(days.value) + 1); days.dispatchEvent(new Event('change')); });

    update();
  });
})();
