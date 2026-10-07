<?php
/**
 * Gabarit de secours (articles, recherches…).
 */
get_header();
?>
  <main>
<?php get_template_part( 'template-parts/page-hero', null, array( 'title' => is_home() ? 'Actualités' : wp_strip_all_tags( get_the_archive_title() ) ) ); ?>
    <section class="section">
      <div class="container narrow entry-content">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
          <article class="entry">
            <h2 class="title title--sm"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <?php the_excerpt(); ?>
          </article>
        <?php endwhile; the_posts_pagination(); else : ?>
          <p>Aucun contenu pour le moment.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>
<?php
get_footer();
