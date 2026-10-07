<?php
/**
 * Sections propres à la pension : vie à la pension, conditions d'admission, horaires.
 */
$id    = get_the_ID();
$life  = cdm_pairs( cdm_meta( 'cdm_life', $id ) );
$dogs  = cdm_lines( cdm_meta( 'cdm_adm_dogs', $id ) );
$cats  = cdm_lines( cdm_meta( 'cdm_adm_cats', $id ) );
$bring = cdm_lines( cdm_meta( 'cdm_bring', $id ) );
$life_icons = array( 'walk', 'bowl', 'heart' );
?>
<?php if ( $life ) : ?>
    <!-- ===== LA VIE À LA PENSION ===== -->
    <section class="section">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Au quotidien</p>
          <h2 class="title">La vie à la pension</h2>
        </div>

        <div class="services__grid services__grid--3">
          <?php foreach ( $life as $i => $card ) : ?>
          <article class="service">
            <div class="service__icon" aria-hidden="true"><?php echo cdm_icon( $life_icons[ $i % 3 ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
            <h3><?php echo esc_html( $card[0] ); ?></h3>
            <p><?php echo esc_html( $card[1] ); ?></p>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

<?php endif; ?>
<?php if ( $dogs || $cats || $bring ) : ?>
    <!-- ===== CONDITIONS & À PRÉVOIR ===== -->
    <section class="section">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Avant le séjour</p>
          <h2 class="title">Conditions d'admission</h2>
          <p class="lead narrow-text">Pour la sécurité et la santé de tous nos pensionnaires, quelques conditions sont demandées.</p>
        </div>

        <div class="info__grid">
          <?php foreach ( array( array( 'Pour les chiens', $dogs, '' ), array( 'Pour les chats', $cats, '' ), array( 'À apporter', $bring, 'accent' ) ) as $card ) : ?>
            <?php if ( ! $card[1] ) { continue; } ?>
          <article class="info-card<?php echo $card[2] ? ' info-card--accent' : ''; ?>">
            <h3><?php echo esc_html( $card[0] ); ?></h3>
            <ul class="checklist<?php echo $card[2] ? ' checklist--light' : ''; ?>">
              <?php foreach ( $card[1] as $line ) : ?>
              <li><?php echo esc_html( $line ); ?></li>
              <?php endforeach; ?>
            </ul>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

<?php endif; ?>
    <!-- ===== HORAIRES ===== -->
    <section class="section section--tinted">
      <div class="container narrow">
        <div class="center">
          <p class="eyebrow">Arrivées &amp; départs</p>
          <h2 class="title">Horaires d'accueil</h2>
          <p class="contact__big"><?php echo esc_html( cdm_opt( 'hours_days' ) . ', ' . cdm_opt( 'hours_time' ) ); ?></p>
          <p class="muted"><?php echo wp_kses_post( cdm_opt( 'hours_note' ) ); ?></p>
        </div>
        <?php get_template_part( 'template-parts/hours-notes' ); ?>
      </div>
    </section>

