<?php wp_enqueue_script( 'cdm-simulateur' ); ?>
    <!-- ===== SIMULATEUR DE PRIX ===== -->
    <section class="section section--tinted" id="simulateur">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Pension canine</p>
          <h2 class="title">Simulez le prix de son séjour</h2>
          <p class="lead narrow-text">Choisissez le gabarit de votre chien et la durée du séjour&nbsp;: l'estimation se calcule instantanément.</p>
        </div>

        <div class="sim" data-simulator>
          <div class="sim__form">
            <fieldset class="sim__field">
              <legend>1. Le gabarit de votre chien</legend>
              <div class="sim__sizes" data-sim-sizes></div>
            </fieldset>

            <div class="sim__field">
              <label class="sim__legend" for="sim-days">2. La durée du séjour</label>
              <div class="sim__duration">
                <div class="sim__stepper">
                  <button type="button" data-sim-minus aria-label="Un jour de moins">−</button>
                  <input id="sim-days" type="number" min="1" max="90" value="3" inputmode="numeric" data-sim-days>
                  <button type="button" data-sim-plus aria-label="Un jour de plus">+</button>
                </div>
                <span class="sim__unit">jours</span>
              </div>
              <input class="sim__range" type="range" min="1" max="30" value="3" aria-label="Durée du séjour en jours" data-sim-range>
              <div class="sim__range-labels" aria-hidden="true"><span>1 j</span><span>15 j</span><span>30 j</span></div>
              <p class="sim__hint" data-sim-hint></p>
            </div>
          </div>

          <div class="sim__result" aria-live="polite">
            <p class="sim__label">Estimation du séjour</p>
            <p class="sim__total"><span data-sim-total>–</span>&nbsp;€</p>
            <p class="sim__detail" data-sim-detail></p>
            <p class="sim__saving" data-sim-saving hidden></p>
            <a href="<?php echo esc_attr( cdm_tel_link() ); ?>" class="btn btn--light">Réserver ce séjour</a>
            <p class="sim__note">Estimation indicative pour un chien. Le prix définitif est confirmé lors de la réservation. Pour les chats, contactez-nous.</p>
          </div>
        </div>

        <table class="sim__table" data-sim-table></table>
      </div>
    </section>

