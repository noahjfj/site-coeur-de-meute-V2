<?php
/**
 * Cartes « Horaires d'accueil » et « Nous contacter ». Argument : heading (bool) — affiche le titre de section.
 */
$heading = ! empty( $args['heading'] );
?>
    <!-- ===== CONTACT ===== -->
    <section class="contact section" id="contact">
      <div class="container">
        <?php if ( $heading ) : ?>
        <div class="center">
          <p class="eyebrow">Nous rencontrer</p>
          <h2 class="title">Contact &amp; horaires</h2>
        </div>
        <?php endif; ?>

        <div class="contact__grid">
          <div class="contact__card">
            <h3>Horaires d'accueil</h3>
            <p class="contact__big"><?php echo esc_html( cdm_opt( 'hours_days' ) ); ?></p>
            <p class="contact__big"><?php echo esc_html( cdm_opt( 'hours_time' ) ); ?></p>
            <p class="muted"><?php echo wp_kses_post( cdm_opt( 'hours_note' ) ); ?></p>
            <?php get_template_part( 'template-parts/hours-notes' ); ?>
          </div>

          <div class="contact__card contact__card--accent">
            <h3>Nous contacter</h3>
            <ul class="contact__list">
              <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z" /><circle cx="12" cy="9" r="2.5" /></svg>
                <span><?php echo esc_html( cdm_opt( 'address_street' ) . ' — ' . cdm_opt( 'address_city' ) ); ?></span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2" /></svg>
                <a href="<?php echo esc_attr( cdm_tel_link() ); ?>"><?php echo esc_html( cdm_opt( 'phone' ) ); ?></a>
              </li>
              <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6 9-6" /></svg>
                <a href="mailto:<?php echo esc_attr( antispambot( cdm_opt( 'email' ) ) ); ?>"><?php echo esc_html( antispambot( cdm_opt( 'email' ) ) ); ?></a>
              </li>
            </ul>
            <a href="<?php echo esc_attr( cdm_tel_link() ); ?>" class="btn btn--light">Appeler maintenant</a>
          </div>
        </div>
        <?php if ( ! empty( $args['map'] ) ) : ?>

        <div class="map">
          <iframe title="Plan d'accès — <?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                  src="https://maps.google.com/maps?q=<?php echo rawurlencode( cdm_opt( 'address_street' ) . ', ' . cdm_opt( 'address_city' ) . ', ' . cdm_opt( 'address_country' ) ); ?>&amp;z=15&amp;output=embed"
                  loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <?php endif; ?>
      </div>
    </section>
