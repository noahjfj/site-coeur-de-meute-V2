<?php
/**
 * Page d'un service.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$id       = get_the_ID();
	$pillar   = cdm_meta( 'cdm_pillar' );
	$is_sim   = cdm_meta( 'cdm_simulator' );
	$facts    = cdm_pairs( cdm_meta( 'cdm_facts' ) );
	$items    = cdm_lines( cdm_meta( 'cdm_items' ) );
	$note     = cdm_meta( 'cdm_note' );

	$services = cdm_get_services();
	$ids      = wp_list_pluck( $services, 'ID' );
	$pos      = array_search( $id, $ids, true );
	$related  = array();
	for ( $k = 1; $k <= 3 && count( $services ) > 1; $k++ ) {
		$next = $services[ ( $pos + $k ) % count( $services ) ];
		if ( $next->ID !== $id ) {
			$related[ $next->ID ] = $next;
		}
	}
	?>
  <main>

<?php get_template_part( 'template-parts/page-hero', null, array(
	'eyebrow'     => $pillar ? 'Nos services · Notre coeur de métier' : 'Nos services',
	'eyebrow_url' => cdm_services_url(),
	'title'       => cdm_meta( 'cdm_hero_title' ) ?: get_the_title(),
	'lead'        => cdm_meta( 'cdm_lead' ),
	'icon'        => cdm_meta( 'cdm_icon' ) ?: 'paw',
	'color'       => cdm_meta( 'cdm_color' ) ?: 'rust',
) ); ?>

    <!-- ===== PRÉSENTATION ===== -->
    <section class="section">
      <div class="container service-detail">
        <div class="service-detail__text">
          <?php the_content(); ?>
          <?php if ( $items ) : ?>
          <h2 class="title title--sm"><?php echo esc_html( cdm_meta( 'cdm_list_title' ) ); ?></h2>
          <ul class="checklist">
            <?php foreach ( $items as $item ) : ?>
            <li><?php echo esc_html( $item ); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <?php if ( $note ) : ?>
          <p class="note"><?php echo wp_kses_post( $note ); ?></p>
          <?php endif; ?>
        </div>

        <aside class="service-aside">
          <h3>En bref</h3>
          <dl>
            <?php foreach ( $facts as $i => $fact ) : ?>
            <div><dt><?php echo esc_html( $fact[0] ); ?></dt><dd><?php echo wp_kses_post( $fact[1] ); ?></dd></div>
              <?php if ( 0 === $i && $is_sim ) : ?>
            <div><dt>Tarif</dt><dd>À partir de <span data-tarif-min><?php echo esc_html( cdm_euros( cdm_tarif_min() ) ); ?></span> € / jour — <a href="#simulateur">simuler mon prix</a></dd></div>
              <?php endif; ?>
            <?php endforeach; ?>
            <div><dt>Rendez-vous</dt><dd><?php echo esc_html( cdm_opt( 'phone' ) ); ?></dd></div>
          </dl>
          <a href="<?php echo esc_attr( cdm_tel_link() ); ?>" class="btn btn--light">Prendre rendez-vous</a>
        </aside>
      </div>
    </section>

<?php
	get_template_part( 'template-parts/steps', null, array(
		'steps'   => cdm_pairs( cdm_meta( 'cdm_steps' ) ),
		'eyebrow' => $is_sim ? 'Premier séjour' : 'Déroulement',
	) );
	if ( $is_sim ) {
		get_template_part( 'template-parts/simulator' );
	}
	if ( cdm_meta( 'cdm_pension' ) ) {
		get_template_part( 'template-parts/pension-infos' );
	}
	?>
    <?php if ( $related ) : ?>
    <!-- ===== AUTRES SERVICES ===== -->
    <section class="section section--tinted">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Découvrir aussi</p>
          <h2 class="title">Nos autres services</h2>
        </div>
        <div class="svc-grid">
          <?php foreach ( $related as $s ) { get_template_part( 'template-parts/svc-card', null, array( 'service' => $s ) ); } ?>
        </div>
        <div class="center more">
          <a href="<?php echo esc_url( cdm_services_url() ); ?>" class="btn btn--outline-dark">Tous nos services</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

<?php get_template_part( 'template-parts/cta' ); ?>
  </main>

<?php
endwhile;
get_footer();
