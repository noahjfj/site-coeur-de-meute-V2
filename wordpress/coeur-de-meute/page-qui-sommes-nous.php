<?php
/**
 * Page « Qui sommes-nous » : présentation (contenu de la page + image mise en avant), valeurs et équipe.
 */
get_header();
while ( have_posts() ) :
	the_post();
	$membres = get_posts( array( 'post_type' => 'membre', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$photo   = has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : CDM_URI . '/assets/img/devanture.jpg';
	?>
  <main>
<?php get_template_part( 'template-parts/page-hero', null, array(
	'eyebrow' => get_the_title(),
	'title'   => cdm_meta( 'cdm_hero_title' ) ?: get_the_title(),
	'lead'    => cdm_meta( 'cdm_lead' ),
) ); ?>

    <!-- ===== PRÉSENTATION ===== -->
    <section class="section">
      <div class="container about__grid">
        <figure class="about__media">
          <span class="blob blob--rust about__blob" aria-hidden="true"></span>
          <img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( get_the_title() . ' — ' . get_bloginfo( 'name' ) ); ?>">
        </figure>

        <div class="about__text entry-content">
          <?php the_content(); ?>
        </div>
      </div>
    </section>

    <!-- ===== VALEURS ===== -->
    <section class="values section section--tinted">
      <div class="container">
        <div class="center">
          <p class="eyebrow">Nos valeurs</p>
          <h2 class="title"><?php echo esc_html( implode( ' · ', array( cdm_opt( 'value1_title' ), cdm_opt( 'value2_title' ), cdm_opt( 'value3_title' ) ) ) ); ?></h2>
        </div>
        <div class="values__grid">
          <?php foreach ( array( 1 => 'blue', 2 => 'rust', 3 => 'sand' ) as $n => $color ) : ?>
          <article class="value">
            <span class="value__shape value__shape--<?php echo esc_attr( $color ); ?>" aria-hidden="true"></span>
            <h3><?php echo esc_html( cdm_opt( "value{$n}_title" ) ); ?></h3>
            <p><?php echo wp_kses_post( cdm_opt( "value{$n}_text" ) ); ?></p>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ( $membres ) : ?>
    <!-- ===== ÉQUIPE ===== -->
    <section class="section">
      <div class="container">
        <div class="center">
          <p class="eyebrow">L'équipe</p>
          <h2 class="title">Ceux qui prennent soin de votre compagnon</h2>
        </div>

        <div class="team__grid">
          <?php foreach ( $membres as $m ) :
            $color = cdm_meta( 'cdm_color', $m->ID ) ?: 'rust';
            $icon  = cdm_meta( 'cdm_icon', $m->ID );
            ?>
          <article class="member">
            <div class="member__avatar member__avatar--<?php echo esc_attr( $color ); ?>" aria-hidden="true">
              <?php
              if ( has_post_thumbnail( $m ) ) {
                echo get_the_post_thumbnail( $m, 'thumbnail', array( 'class' => 'member__photo', 'alt' => '' ) );
              } elseif ( $icon ) {
                echo cdm_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput
              } else {
                echo esc_html( mb_substr( get_the_title( $m ), 0, 1 ) );
              }
              ?>
            </div>
            <h3><?php echo esc_html( get_the_title( $m ) ); ?></h3>
            <p class="member__role"><?php echo esc_html( cdm_meta( 'cdm_role', $m->ID ) ); ?></p>
            <p><?php echo esc_html( get_the_excerpt( $m ) ); ?></p>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

<?php get_template_part( 'template-parts/cta' ); ?>
  </main>
	<?php
endwhile;
get_footer();
