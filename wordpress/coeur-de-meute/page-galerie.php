<?php
/**
 * Page « Galerie » : les photos se gèrent avec un bloc « Galerie » dans le contenu de la page.
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
  <main>
<?php get_template_part( 'template-parts/page-hero', null, array(
	'eyebrow' => get_the_title(),
	'title'   => cdm_meta( 'cdm_hero_title' ) ?: get_the_title(),
	'lead'    => cdm_meta( 'cdm_lead' ),
) ); ?>

    <!-- ===== GALERIE ===== -->
    <section class="section">
      <div class="container entry-gallery">
        <?php the_content(); ?>
      </div>
    </section>

<?php get_template_part( 'template-parts/cta' ); ?>
  </main>

  <!-- Visionneuse plein écran -->
  <div class="lightbox" id="lightbox" hidden>
    <button type="button" class="lightbox__close" aria-label="Fermer">&times;</button>
    <img src="" alt="">
  </div>
	<?php
endwhile;
get_footer();
