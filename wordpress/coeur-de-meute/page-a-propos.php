<?php
/**
 * Page « À propos » : contact, plan d'accès et questions fréquentes.
 */
get_header();
while ( have_posts() ) :
	the_post();
	$faqs = get_posts( array( 'post_type' => 'faq', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	?>
  <main>
<?php
	get_template_part( 'template-parts/page-hero', null, array(
		'eyebrow' => get_the_title(),
		'title'   => cdm_meta( 'cdm_hero_title' ) ?: get_the_title(),
		'lead'    => cdm_meta( 'cdm_lead' ),
	) );
	get_template_part( 'template-parts/contact', null, array( 'map' => true ) );
	?>

    <?php if ( $faqs ) : ?>
    <!-- ===== FAQ ===== -->
    <section class="section section--tinted">
      <div class="container narrow">
        <div class="center">
          <p class="eyebrow">Questions fréquentes</p>
          <h2 class="title">Vous vous posez des questions&nbsp;?</h2>
        </div>

        <div class="faq">
          <?php foreach ( $faqs as $faq ) : ?>
          <details>
            <summary><?php echo esc_html( get_the_title( $faq ) ); ?></summary>
            <?php echo wp_kses_post( wpautop( $faq->post_content ) ); ?>
          </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if ( trim( get_the_content() ) ) : ?>
    <section class="section">
      <div class="container narrow entry-content"><?php the_content(); ?></div>
    </section>
    <?php endif; ?>
  </main>
	<?php
endwhile;
get_footer();
