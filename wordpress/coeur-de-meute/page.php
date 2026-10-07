<?php
/**
 * Page standard.
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
  <main>
<?php get_template_part( 'template-parts/page-hero', null, array(
	'eyebrow' => cdm_meta( 'cdm_hero_title' ) ? get_the_title() : '',
	'title'   => cdm_meta( 'cdm_hero_title' ) ?: get_the_title(),
	'lead'    => cdm_meta( 'cdm_lead' ),
) ); ?>

    <section class="section">
      <div class="container narrow entry-content">
        <?php the_content(); ?>
      </div>
    </section>

<?php get_template_part( 'template-parts/cta' ); ?>
  </main>
	<?php
endwhile;
get_footer();
