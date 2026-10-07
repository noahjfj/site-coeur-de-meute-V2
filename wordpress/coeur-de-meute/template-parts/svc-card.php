<?php $s = $args['service']; ?>
          <a class="svc-card" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
            <span class="svc-card__icon svc-card__icon--<?php echo esc_attr( cdm_meta( 'cdm_color', $s->ID ) ?: 'rust' ); ?>" aria-hidden="true"><?php echo cdm_icon( cdm_meta( 'cdm_icon', $s->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
            <h3><?php echo esc_html( get_the_title( $s ) ); ?></h3>
            <p><?php echo esc_html( cdm_short( $s ) ); ?></p>
            <span class="svc-card__more">En savoir plus</span>
          </a>
