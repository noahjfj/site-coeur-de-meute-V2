<?php
/**
 * Page introuvable.
 */
get_header();
?>
  <main>
<?php get_template_part( 'template-parts/page-hero', null, array(
	'eyebrow' => 'Erreur 404',
	'title'   => 'Cette page s\'est échappée…',
	'lead'    => 'La page que vous cherchez n\'existe pas ou a été déplacée.',
) ); ?>
    <section class="section">
      <div class="container narrow center">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn">Retour à l'accueil</a>
      </div>
    </section>
  </main>
<?php
get_footer();
