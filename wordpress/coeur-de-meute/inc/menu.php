<?php
/**
 * Menu principal : même balisage que le design (sous-menu « Nos services » déroulant).
 * Si l'élément « Services » du menu n'a pas de sous-éléments, la liste des services est ajoutée automatiquement.
 */

defined( 'ABSPATH' ) || exit;

function cdm_sub_toggle() {
	return '<button class="sub-toggle" type="button" aria-expanded="false" aria-label="Afficher la liste des services">'
		. '<svg viewBox="0 0 12 8" aria-hidden="true"><path d="M1 1.5 L6 6.5 L11 1.5" /></svg></button>';
}

/** Sous-menu automatique avec tous les services publiés */
function cdm_services_submenu() {
	$html = '<ul class="sub-menu">';
	foreach ( cdm_get_services() as $service ) {
		$current = is_single( $service->ID ) ? ' aria-current="page"' : '';
		$html   .= sprintf( '<li><a href="%s"%s>%s</a></li>', esc_url( get_permalink( $service ) ), $current, esc_html( get_the_title( $service ) ) );
	}
	return $html . '</ul>';
}

function cdm_is_services_section() {
	return is_post_type_archive( 'service' ) || is_singular( 'service' );
}

class CDM_Nav_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$output .= cdm_sub_toggle() . '<ul class="sub-menu">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes      = empty( $item->classes ) ? array() : (array) $item->classes;
		$has_children = in_array( 'menu-item-has-children', $classes, true );
		$is_services  = 'post_type_archive' === $item->type && 'service' === $item->object;
		$auto_sub     = $is_services && ! $has_children && 0 === $depth;

		$li_class = array();
		if ( $has_children || $auto_sub ) {
			$li_class[] = 'has-sub';
		}
		$current = $item->current || ( $is_services && cdm_is_services_section() )
			|| ( 0 === $depth && ( $item->current_item_ancestor || $item->current_item_parent ) );

		$output .= '<li' . ( $li_class ? ' class="' . esc_attr( implode( ' ', $li_class ) ) . '"' : '' ) . '>';
		$output .= sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $item->url ),
			$item->target ? ' target="' . esc_attr( $item->target ) . '"' : '',
			$current ? ' aria-current="page"' : '',
			esc_html( $item->title )
		);
		if ( $auto_sub ) {
			$output .= cdm_sub_toggle() . cdm_services_submenu();
		}
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/** Menu utilisé tant qu'aucun menu n'est choisi dans Apparence → Menus */
function cdm_fallback_menu() {
	$cur = function ( $active ) {
		return $active ? ' aria-current="page"' : '';
	};
	$items = array(
		array( home_url( '/' ), 'Accueil', is_front_page() ),
		array( cdm_page_url( 'qui-sommes-nous' ), 'Qui sommes-nous', is_page( 'qui-sommes-nous' ) ),
		'services',
		array( cdm_page_url( 'galerie' ), 'Galerie', is_page( 'galerie' ) ),
		array( cdm_page_url( 'a-propos' ), 'À propos', is_page( 'a-propos' ) ),
	);
	echo '<ul>';
	foreach ( $items as $item ) {
		if ( 'services' === $item ) {
			printf(
				'<li class="has-sub"><a href="%s"%s>Nos services</a>%s%s</li>',
				esc_url( cdm_services_url() ),
				$cur( cdm_is_services_section() ),
				cdm_sub_toggle(), // phpcs:ignore WordPress.Security.EscapeOutput
				cdm_services_submenu() // phpcs:ignore WordPress.Security.EscapeOutput
			);
			continue;
		}
		printf( '<li><a href="%s"%s>%s</a></li>', esc_url( $item[0] ), $cur( $item[2] ), esc_html( $item[1] ) );
	}
	echo '</ul>';
}

/** Liens du pied de page (premier niveau du menu principal) */
function cdm_footer_links() {
	$locations = get_nav_menu_locations();
	$items     = ! empty( $locations['principal'] ) ? wp_get_nav_menu_items( $locations['principal'] ) : false;
	echo '<ul class="footer__links">';
	if ( $items ) {
		foreach ( $items as $item ) {
			if ( ! $item->menu_item_parent ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( $item->url ), esc_html( $item->title ) );
			}
		}
	} else {
		foreach ( array(
			array( home_url( '/' ), 'Accueil' ),
			array( cdm_page_url( 'qui-sommes-nous' ), 'Qui sommes-nous' ),
			array( cdm_services_url(), 'Nos services' ),
			array( cdm_page_url( 'galerie' ), 'Galerie' ),
			array( cdm_page_url( 'a-propos' ), 'À propos' ),
		) as $link ) {
			printf( '<li><a href="%s">%s</a></li>', esc_url( $link[0] ), esc_html( $link[1] ) );
		}
	}
	echo '</ul>';
}
