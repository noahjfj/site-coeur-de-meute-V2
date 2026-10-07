    <!-- ===== APPEL À L'ACTION ===== -->
    <section class="cta">
      <div class="container cta__inner">
        <div>
          <h2><?php echo esc_html( cdm_opt( 'cta_title' ) ); ?></h2>
          <p><?php echo wp_kses_post( cdm_opt( 'cta_text' ) ); ?></p>
        </div>
        <div class="cta__actions">
          <a href="<?php echo esc_attr( cdm_tel_link() ); ?>" class="btn btn--light"><?php echo esc_html( cdm_opt( 'phone' ) ); ?></a>
          <a href="<?php echo esc_url( cdm_page_url( 'a-propos' ) . '#contact' ); ?>" class="btn btn--outline">Nous contacter</a>
        </div>
      </div>
    </section>
