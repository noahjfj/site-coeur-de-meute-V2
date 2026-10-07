<?php
$s     = $args['service'];
$color = cdm_meta( 'cdm_color', $s->ID ) === 'rust' ? 'rust' : 'blue';
?>
          <a class="pillar pillar--<?php echo esc_attr( $color ); ?>" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
            <span class="pillar__icon" aria-hidden="true"><?php echo cdm_icon( cdm_meta( 'cdm_icon', $s->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <span class="pillar__tag">Notre coeur de métier</span>
            <h3><?php echo esc_html( get_the_title( $s ) ); ?></h3>
            <p><?php echo esc_html( cdm_short( $s ) ); ?></p>
            <span class="pillar__more">Découvrir</span>
          </a>
