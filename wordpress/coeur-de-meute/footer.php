<?php
/**
 * Pied de page.
 */
?>
  <!-- ===== FOOTER ===== -->
  <footer class="footer">
    <div class="container footer__grid">
      <div class="footer__col footer__col--brand">
        <p class="footer__logo">Coeur <span class="logo__de logo__de--small">de</span> Meute</p>
        <p><?php echo wp_kses_post( implode( '<br>', cdm_lines( cdm_opt( 'footer_text' ) ) ) ); ?></p>
        <p class="footer__tag"><?php echo esc_html( cdm_opt( 'hero_slogan' ) ); ?></p>
      </div>

      <div class="footer__col">
        <h4>Coordonnées</h4>
        <p><?php echo esc_html( cdm_opt( 'address_street' ) ); ?><br><?php echo esc_html( cdm_opt( 'address_city' ) . ' — ' . cdm_opt( 'address_country' ) ); ?></p>
        <p><a href="<?php echo esc_attr( cdm_tel_link() ); ?>"><?php echo esc_html( cdm_opt( 'phone' ) ); ?></a><br>
           <a href="mailto:<?php echo esc_attr( antispambot( cdm_opt( 'email' ) ) ); ?>"><?php echo esc_html( antispambot( cdm_opt( 'email' ) ) ); ?></a></p>
      </div>

      <div class="footer__col">
        <h4>Horaires</h4>
        <p><?php echo esc_html( cdm_opt( 'hours_days' ) ); ?><br><?php echo esc_html( cdm_opt( 'hours_time' ) ); ?>, sauf exceptions</p>
        <p>Heure d'accueil convenue au préalable.<br>Rencontres sur rendez-vous.</p>
        <?php if ( cdm_opt( 'vet' ) ) : ?>
        <h4>Vétérinaire référent</h4>
        <p><?php echo esc_html( cdm_opt( 'vet' ) ); ?></p>
        <?php endif; ?>
      </div>

      <div class="footer__col">
        <h4>Navigation</h4>
        <?php cdm_footer_links(); ?>
      </div>
    </div>

    <div class="footer__bottom">
      <div class="container">
        <p>© <span id="year"><?php echo esc_html( gmdate( 'Y' ) ); ?></span> <?php bloginfo( 'name' ); ?> — Tous droits réservés.</p>
      </div>
    </div>
  </footer>

<?php wp_footer(); ?>
</body>
</html>
