<?php
/**
 * Page « Nos services » : services mis en avant, puis les autres en cartes.
 */
get_header();

$services = cdm_get_services();
$pillars  = array_values( array_filter( $services, function ( $s ) { return cdm_meta( 'cdm_pillar', $s->ID ); } ) );
$others   = array_filter( $services, function ( $s ) { return ! cdm_meta( 'cdm_pillar', $s->ID ); } );
?>
  <main>

<?php get_template_part( 'template-parts/page-hero', null, array(
	'eyebrow' => 'Nos services',
	'title'   => 'Un centre pluridisciplinaire pour chiens et chats',
	'lead'    => 'Éducation, pension, comportement et bien-être : toutes nos compétences réunies sous un même toit, au service de votre compagnon.',
) ); ?>

    <!-- ===== PILIERS ===== -->
    <section class="section">
      <div class="container">
        <?php foreach ( $pillars as $i => $s ) :
          $paras = array_values( array_filter( array_map( 'trim', preg_split( '/\n\s*\n/', wp_strip_all_tags( $s->post_content ) ) ) ) );
          ?>
        <article class="feature<?php echo $i % 2 ? ' feature--reverse' : ''; ?>">
          <div class="feature__visual feature__visual--<?php echo esc_attr( cdm_meta( 'cdm_color', $s->ID ) ?: 'rust' ); ?>" aria-hidden="true">
            <?php echo cdm_icon( cdm_meta( 'cdm_icon', $s->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          </div>
          <div class="feature__text">
            <p class="eyebrow">Notre coeur de métier</p>
            <h2 class="title"><?php echo esc_html( get_the_title( $s ) ); ?></h2>
            <p><?php echo esc_html( $paras ? $paras[0] : cdm_short( $s ) ); ?></p>
            <ul class="checklist">
              <?php foreach ( array_slice( cdm_lines( cdm_meta( 'cdm_items', $s->ID ) ), 0, 3 ) as $item ) : ?>
              <li><?php echo esc_html( $item ); ?></li>
              <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url( get_permalink( $s ) ); ?>" class="link-arrow">Découvrir ce service</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <?php if ( $others ) : ?>
    <!-- ===== AUTRES SERVICES ===== -->
    <section class="section section--tinted">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Comportement &amp; bien-être</p>
          <h2 class="title">Pour aller plus loin</h2>
          <p class="lead narrow-text">Parce que le bien-être de votre animal passe aussi par son corps, ses émotions et son environnement.</p>
        </div>
        <div class="svc-grid">
          <?php foreach ( $others as $s ) { get_template_part( 'template-parts/svc-card', null, array( 'service' => $s ) ); } ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

<?php get_template_part( 'template-parts/cta' ); ?>
  </main>

<?php
get_footer();
