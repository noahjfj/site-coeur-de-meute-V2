<?php
/**
 * Article.
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
  <main>
<?php get_template_part( 'template-parts/page-hero', null, array( 'eyebrow' => get_the_date(), 'title' => get_the_title() ) ); ?>
    <section class="section">
      <div class="container narrow entry-content"><?php the_content(); ?></div>
    </section>
<?php get_template_part( 'template-parts/cta' ); ?>
  </main>
	<?php
endwhile;
get_footer();
