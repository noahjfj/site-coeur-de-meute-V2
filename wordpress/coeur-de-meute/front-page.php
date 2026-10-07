<?php
/**
 * Page d'accueil.
 */
get_header();

$services = cdm_get_services();
$pillars  = array_filter( $services, function ( $s ) { return cdm_meta( 'cdm_pillar', $s->ID ); } );
$others   = array_filter( $services, function ( $s ) { return ! cdm_meta( 'cdm_pillar', $s->ID ); } );
$pension  = current( array_filter( $services, function ( $s ) { return cdm_meta( 'cdm_simulator', $s->ID ); } ) );

$about_img = cdm_opt( 'about_image' ) ? wp_get_attachment_image_url( cdm_opt( 'about_image' ), 'large' ) : CDM_URI . '/assets/img/devanture.jpg';
$slogan    = array_map( 'trim', explode( '·', cdm_opt( 'hero_slogan' ) ) );
?>

  <!-- ===== HERO ===== -->
  <section class="hero">
    <span class="blob blob--rust hero__blob-1" aria-hidden="true"></span>
    <span class="blob blob--sand hero__blob-2" aria-hidden="true"></span>
    <span class="blob blob--blue hero__blob-3" aria-hidden="true"></span>
    <span class="blob blob--brown hero__blob-4" aria-hidden="true"></span>

    <div class="hero__inner">
      <div class="hero__emblem">
        <img src="<?php echo esc_url( CDM_URI . '/assets/img/logo-coeur-de-meute.svg' ); ?>" alt="Logo Coeur de Meute : une femme entourée de ses chiens dans un coeur" width="300" height="264">
      </div>

      <p class="hero__kicker"><?php echo esc_html( cdm_opt( 'hero_kicker' ) ); ?></p>

      <h1 class="logo">
        <span>Coeur</span>
        <span class="logo__de">de</span>
        <span>Meute</span>
      </h1>

      <p class="tagline"><?php echo implode( '<span>·</span>', array_map( 'esc_html', $slogan ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>

      <a href="#contact" class="btn"><?php echo esc_html( cdm_opt( 'hero_button' ) ); ?></a>
    </div>
  </section>

  <main>

    <!-- ===== INTRO ===== -->
    <section class="intro section">
      <div class="container narrow center">
        <p class="welcome"><?php echo esc_html( cdm_opt( 'welcome' ) ); ?></p>
        <h2 class="title"><?php echo esc_html( cdm_opt( 'intro_title' ) ); ?></h2>
        <p class="lead"><?php echo wp_kses_post( cdm_opt( 'intro_text' ) ); ?></p>
        <span class="divider" aria-hidden="true"></span>
      </div>
    </section>

    <!-- ===== À PROPOS ===== -->
    <section class="about section section--tinted">
      <div class="container about__grid">
        <figure class="about__media">
          <span class="blob blob--rust about__blob" aria-hidden="true"></span>
          <img src="<?php echo esc_url( $about_img ); ?>" alt="L'équipe de Coeur de Meute devant la pension">
        </figure>

        <div class="about__text">
          <p class="eyebrow">Qui sommes-nous ?</p>
          <h2 class="title"><?php echo esc_html( cdm_opt( 'about_title' ) ); ?></h2>
          <?php foreach ( cdm_lines( cdm_opt( 'about_text' ) ) as $para ) : ?>
          <p><?php echo wp_kses_post( $para ); ?></p>
          <?php endforeach; ?>
          <ul class="checklist">
            <?php foreach ( cdm_lines( cdm_opt( 'about_list' ) ) as $item ) : ?>
            <li><?php echo esc_html( $item ); ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="<?php echo esc_url( cdm_page_url( 'qui-sommes-nous' ) ); ?>" class="link-arrow">Découvrir l'équipe</a>
        </div>
      </div>
    </section>

    <!-- ===== SERVICES ===== -->
    <section class="services section">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Nos services</p>
          <h2 class="title">Un centre pluridisciplinaire pour chiens et chats</h2>
        </div>

        <?php if ( $pillars ) : ?>
        <div class="pillars">
          <?php foreach ( $pillars as $s ) { get_template_part( 'template-parts/pillar', null, array( 'service' => $s ) ); } ?>
        </div>
        <?php endif; ?>

        <?php if ( $others ) : ?>
        <p class="subhead">Et aussi, pour leur bien-être</p>
        <div class="svc-grid svc-grid--compact">
          <?php foreach ( $others as $s ) { get_template_part( 'template-parts/svc-card', null, array( 'service' => $s ) ); } ?>
        </div>
        <?php endif; ?>

        <div class="center more">
          <a href="<?php echo esc_url( cdm_services_url() ); ?>" class="btn btn--outline-dark">Voir tous nos services</a>
        </div>
      </div>
    </section>

    <?php
    if ( $pension ) {
      get_template_part( 'template-parts/steps', null, array(
        'steps'   => cdm_pairs( cdm_meta( 'cdm_steps', $pension->ID ) ),
        'eyebrow' => 'Premier séjour',
        'button'  => array( get_permalink( $pension ), 'Découvrir la pension' ),
      ) );
      get_template_part( 'template-parts/simulator' );
    }
    get_template_part( 'template-parts/contact', null, array( 'heading' => true ) );
    ?>
  </main>

<?php
get_footer();
