<?php
/**
 * Tarifs de la pension : page d'administration « Tarifs pension » et données du simulateur.
 */

defined( 'ABSPATH' ) || exit;

function cdm_default_tarifs() {
	return array(
		'seuilJours' => 5,
		'gabarits'   => array(
			array( 'id' => 'petit', 'nom' => 'Petit chien', 'description' => "Jusqu'à 10 kg", 'prix' => 22, 'prixLongSejour' => 20 ),
			array( 'id' => 'moyen', 'nom' => 'Chien moyen', 'description' => 'De 10 à 25 kg', 'prix' => 26, 'prixLongSejour' => 24 ),
			array( 'id' => 'grand', 'nom' => 'Grand chien', 'description' => 'Plus de 25 kg', 'prix' => 28, 'prixLongSejour' => 26 ),
		),
	);
}

function cdm_get_tarifs() {
	$tarifs = get_option( 'cdm_tarifs' );
	return ( is_array( $tarifs ) && ! empty( $tarifs['gabarits'] ) ) ? $tarifs : cdm_default_tarifs();
}

/** Prix le plus bas (affiché « À partir de … ») */
function cdm_tarif_min() {
	$min = null;
	foreach ( cdm_get_tarifs()['gabarits'] as $g ) {
		$low = min( (float) $g['prix'], (float) $g['prixLongSejour'] );
		$min = null === $min ? $low : min( $min, $low );
	}
	return $min;
}

function cdm_euros( $n ) {
	return rtrim( rtrim( number_format( (float) $n, 2, ',', ' ' ), '0' ), ',' );
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Tarifs de la pension', 'Tarifs pension', 'edit_pages', 'cdm-tarifs', 'cdm_render_tarifs_page', 'dashicons-money-alt', 8 );
} );

function cdm_render_tarifs_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$message = '';
	$error   = '';

	if ( isset( $_POST['cdm_tarifs_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['cdm_tarifs_nonce'] ), 'cdm_save_tarifs' ) ) {
		$seuil    = isset( $_POST['seuilJours'] ) ? absint( $_POST['seuilJours'] ) : 0;
		$current  = cdm_get_tarifs();
		$gabarits = array();
		foreach ( $current['gabarits'] as $i => $g ) {
			$nom  = isset( $_POST['nom'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['nom'][ $i ] ) ) : '';
			$desc = isset( $_POST['description'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['description'][ $i ] ) ) : '';
			$prix = isset( $_POST['prix'][ $i ] ) ? (float) str_replace( ',', '.', wp_unslash( $_POST['prix'][ $i ] ) ) : 0;
			$long = isset( $_POST['prixLongSejour'][ $i ] ) ? (float) str_replace( ',', '.', wp_unslash( $_POST['prixLongSejour'][ $i ] ) ) : 0;
			if ( '' === $nom || $prix <= 0 || $long <= 0 || $prix > 1000 || $long > 1000 ) {
				$error = sprintf( 'Vérifiez la ligne « %s » : un nom et des prix entre 0 et 1000 € sont nécessaires.', $nom ? $nom : $g['nom'] );
				break;
			}
			$gabarits[] = array(
				'id'             => $g['id'],
				'nom'            => $nom,
				'description'    => $desc,
				'prix'           => round( $prix, 2 ),
				'prixLongSejour' => round( $long, 2 ),
			);
		}
		if ( ! $error && ( $seuil < 1 || $seuil > 60 ) ) {
			$error = 'Le nombre de jours doit être compris entre 1 et 60.';
		}
		if ( ! $error ) {
			update_option( 'cdm_tarifs', array( 'seuilJours' => $seuil, 'gabarits' => $gabarits, 'misAJour' => current_time( 'mysql' ) ), false );
			$message = 'Prix enregistrés ! Ils sont déjà visibles sur le site.';
		}
	}

	$tarifs = cdm_get_tarifs();
	?>
	<div class="wrap">
		<h1>Tarifs de la pension canine</h1>
		<p>Ces prix sont utilisés par le simulateur de l'accueil et de la page Pension, ainsi que pour le « À partir de … € ».</p>

		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php elseif ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'cdm_save_tarifs', 'cdm_tarifs_nonce' ); ?>
			<h2>Règle du tarif long séjour</h2>
			<p>
				<label>Le tarif long séjour s'applique quand le séjour dépasse
					<input type="number" name="seuilJours" min="1" max="60" step="1" value="<?php echo esc_attr( $tarifs['seuilJours'] ); ?>" class="small-text"> jours.
				</label>
			</p>

			<h2>Prix par jour</h2>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr><th>Gabarit</th><th>Description</th><th>Prix / jour (€)</th><th>Prix long séjour / jour (€)</th></tr>
				</thead>
				<tbody>
				<?php foreach ( $tarifs['gabarits'] as $i => $g ) : ?>
					<tr>
						<td><input type="text" name="nom[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $g['nom'] ); ?>" class="regular-text" style="width:100%"></td>
						<td><input type="text" name="description[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $g['description'] ); ?>" style="width:100%"></td>
						<td><input type="number" name="prix[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $g['prix'] ); ?>" min="0.5" max="1000" step="0.5" class="small-text"></td>
						<td><input type="number" name="prixLongSejour[<?php echo (int) $i; ?>]" value="<?php echo esc_attr( $g['prixLongSejour'] ); ?>" min="0.5" max="1000" step="0.5" class="small-text"></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( ! empty( $tarifs['misAJour'] ) ) : ?>
				<p class="description">Dernière modification : <?php echo esc_html( mysql2date( 'j F Y à H\hi', $tarifs['misAJour'] ) ); ?></p>
			<?php endif; ?>
			<?php submit_button( 'Enregistrer les nouveaux prix' ); ?>
		</form>
	</div>
	<?php
}
