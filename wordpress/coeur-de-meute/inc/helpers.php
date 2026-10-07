<?php
/**
 * Fonctions utilitaires du thème.
 */

defined( 'ABSPATH' ) || exit;

/** Icônes des services (tracés SVG) */
function cdm_icons() {
	return array(
		'paw'       => array( 'Patte', '<path d="M24 24 C17 24 13 32 15 37 C17 41 21 39 24 39 C27 39 31 41 33 37 C35 32 31 24 24 24 Z" /><circle cx="16" cy="16" r="4" /><circle cx="32" cy="16" r="4" /><circle cx="10" cy="27" r="3.5" /><circle cx="38" cy="27" r="3.5" />' ),
		'house'     => array( 'Maison', '<path d="M6 22 L24 8 L42 22 V42 H6 Z" /><path d="M18 42 V30 H30 V42" />' ),
		'shield'    => array( 'Bouclier', '<path d="M24 6 L39 12 V23 C39 32 33 39 24 42 C15 39 9 32 9 23 V12 Z" /><path d="M17 24 L22 29 L31 19" />' ),
		'cat'       => array( 'Chat', '<path d="M12 40 C8 34 9 24 12 18 L11 7 L19 13 C22 12 26 12 29 13 L37 7 L36 18 C39 24 40 34 36 40 Z" /><circle cx="19" cy="25" r="1.5" /><circle cx="29" cy="25" r="1.5" /><path d="M22 31 L24 33 L26 31" />' ),
		'spine'     => array( 'Colonne', '<rect x="17" y="5" width="14" height="7" rx="3.5" /><rect x="16" y="15" width="16" height="7" rx="3.5" /><rect x="16" y="25" width="16" height="7" rx="3.5" /><rect x="17" y="35" width="14" height="7" rx="3.5" />' ),
		'wave'      => array( 'Onde', '<circle cx="24" cy="24" r="17" /><path d="M10 24 C14 17 19 17 24 24 C29 31 34 31 38 24" />' ),
		'homeheart' => array( 'Maison coeur', '<path d="M6 22 L24 8 L42 22 V42 H6 Z" /><path d="M24 36 C24 36 15 30.5 15 25 C15 22 17.2 20 19.6 20 C21.6 20 23.2 21.2 24 23 C24.8 21.2 26.4 20 28.4 20 C30.8 20 33 22 33 25 C33 30.5 24 36 24 36 Z" />' ),
		'cross'     => array( 'Croix', '<path d="M20 8 H28 V20 H40 V28 H28 V40 H20 V28 H8 V20 H20 Z" />' ),
		'walk'      => array( 'Promenade', '<path d="M10 38 C14 26 20 20 24 20 C28 20 34 26 38 38" /><circle cx="24" cy="12" r="5" /><path d="M6 38 H42" />' ),
		'bowl'      => array( 'Gamelle', '<path d="M8 26 H40 C40 34 33 40 24 40 C15 40 8 34 8 26 Z" /><path d="M18 20 C18 16 22 16 22 12 M26 20 C26 16 30 16 30 12" />' ),
		'heart'     => array( 'Coeur', '<path d="M24 40 C24 40 8 30 8 19 C8 13 12.5 9 17.5 9 C21 9 23 11 24 13 C25 11 27 9 30.5 9 C35.5 9 40 13 40 19 C40 30 24 40 24 40 Z" />' ),
	);
}

function cdm_icon( $name ) {
	$icons = cdm_icons();
	$path  = isset( $icons[ $name ] ) ? $icons[ $name ][1] : $icons['paw'][1];
	return '<svg viewBox="0 0 48 48" aria-hidden="true">' . $path . '</svg>';
}

function cdm_colors() {
	return array(
		'rust'  => 'Terracotta',
		'blue'  => 'Bleu poudré',
		'sand'  => 'Sable',
		'brown' => 'Brun',
	);
}

/** Réglage du Personnalisateur avec sa valeur par défaut */
function cdm_opt( $key ) {
	$defaults = cdm_customizer_defaults();
	return get_theme_mod( $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/** Lignes d'un champ texte (une entrée par ligne) */
function cdm_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ), 'strlen' ) );
}

/** Lignes « Titre | Texte » */
function cdm_pairs( $text ) {
	$out = array();
	foreach ( cdm_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
	}
	return $out;
}

/** Lien « tel: » à partir du numéro affiché (0455 / 10.97.97 → +32455109797) */
function cdm_tel_link( $phone = null ) {
	$digits = preg_replace( '/[^\d+]/', '', null === $phone ? cdm_opt( 'phone' ) : $phone );
	if ( 0 === strpos( $digits, '0' ) ) {
		$digits = '+32' . substr( $digits, 1 );
	}
	return 'tel:' . $digits;
}

/** URL d'une page à partir de son identifiant (slug) */
function cdm_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

function cdm_services_url() {
	return get_post_type_archive_link( 'service' );
}

/** Services, triés selon l'ordre défini dans l'administration */
function cdm_get_services( $args = array() ) {
	return get_posts( array_merge( array(
		'post_type'      => 'service',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'post_status'    => 'publish',
	), $args ) );
}

function cdm_meta( $key, $post_id = null ) {
	return get_post_meta( $post_id ? $post_id : get_the_ID(), $key, true );
}

/** Texte court : extrait si renseigné, sinon début du contenu */
function cdm_short( $post ) {
	if ( has_excerpt( $post ) ) {
		return get_the_excerpt( $post );
	}
	return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 22 );
}
