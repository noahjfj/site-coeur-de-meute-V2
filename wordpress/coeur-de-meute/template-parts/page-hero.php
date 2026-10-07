<?php
/**
 * En-tête des pages intérieures.
 * Arguments : eyebrow, title, lead, icon, color, eyebrow_url
 */
$a = wp_parse_args( $args, array( 'eyebrow' => '', 'title' => get_the_title(), 'lead' => '', 'icon' => '', 'color' => 'rust', 'eyebrow_url' => '' ) );
?>
    <!-- ===== EN-TÊTE DE PAGE ===== -->
    <section class="page-hero">
      <span class="blob blob--rust page-hero__blob-1" aria-hidden="true"></span>
      <span class="blob blob--sand page-hero__blob-2" aria-hidden="true"></span>
      <span class="blob blob--blue page-hero__blob-3" aria-hidden="true"></span>
      <div class="container narrow center">
        <?php if ( $a['icon'] ) : ?>
        <div class="page-hero__icon page-hero__icon--<?php echo esc_attr( $a['color'] ); ?>" aria-hidden="true"><?php echo cdm_icon( $a['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
        <?php endif; ?>
        <?php if ( $a['eyebrow'] ) : ?>
        <p class="eyebrow"><?php if ( $a['eyebrow_url'] ) : ?><a href="<?php echo esc_url( $a['eyebrow_url'] ); ?>"><?php echo esc_html( $a['eyebrow'] ); ?></a><?php else : echo esc_html( $a['eyebrow'] ); endif; ?></p>
        <?php endif; ?>
        <h1 class="page-hero__title"><?php echo esc_html( $a['title'] ); ?></h1>
        <?php if ( $a['lead'] ) : ?>
        <p class="lead"><?php echo wp_kses_post( $a['lead'] ); ?></p>
        <?php endif; ?>
      </div>
    </section>
