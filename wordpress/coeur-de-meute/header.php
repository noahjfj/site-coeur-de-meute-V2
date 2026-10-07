<?php
/**
 * En-tête du site : bannière et menu.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <!-- ===== EN-TÊTE / MENU ===== -->
  <header class="site-header" id="site-header">
    <div class="container site-header__inner">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — accueil">
        <span>Coeur</span><span class="logo__de">de</span><span>Meute</span>
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
        <span class="sr-only">Ouvrir le menu</span>
      </button>

      <nav class="site-nav" id="site-nav" aria-label="Menu principal">
        <?php
        wp_nav_menu( array(
          'theme_location' => 'principal',
          'container'      => false,
          'items_wrap'     => '<ul>%3$s</ul>',
          'walker'         => new CDM_Nav_Walker(),
          'fallback_cb'    => 'cdm_fallback_menu',
          'depth'          => 2,
        ) );
        ?>
        <a href="<?php echo esc_attr( cdm_tel_link() ); ?>" class="btn btn--small site-nav__cta"><?php echo esc_html( cdm_opt( 'phone' ) ); ?></a>
      </nav>
    </div>
  </header>
