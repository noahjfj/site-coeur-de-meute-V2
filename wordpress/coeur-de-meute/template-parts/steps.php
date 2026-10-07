<?php
/**
 * Étapes « Comment ça se passe ? ». Arguments : steps (liste [titre, texte]), eyebrow, title, button (url, label)
 */
$a = wp_parse_args( $args, array( 'steps' => array(), 'eyebrow' => 'Déroulement', 'title' => 'Comment ça se passe ?', 'button' => null ) );
if ( ! $a['steps'] ) {
	return;
}
?>
    <!-- ===== DÉROULEMENT ===== -->
    <section class="steps">
      <div class="container">
        <div class="center">
          <p class="eyebrow eyebrow--light"><?php echo esc_html( $a['eyebrow'] ); ?></p>
          <h2 class="title"><?php echo esc_html( $a['title'] ); ?></h2>
        </div>

        <ol class="steps__grid">
          <?php foreach ( $a['steps'] as $i => $step ) : ?>
          <li class="step">
            <span class="step__num"><?php echo (int) $i + 1; ?></span>
            <h3><?php echo esc_html( $step[0] ); ?></h3>
            <p><?php echo esc_html( $step[1] ); ?></p>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php if ( $a['button'] ) : ?>

        <div class="center more">
          <a href="<?php echo esc_url( $a['button'][0] ); ?>" class="btn btn--light"><?php echo esc_html( $a['button'][1] ); ?></a>
        </div>
        <?php endif; ?>
      </div>
    </section>
