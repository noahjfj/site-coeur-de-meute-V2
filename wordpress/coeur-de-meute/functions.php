<?php
/**
 * Thème Coeur de Meute — fonctions principales.
 */

defined( 'ABSPATH' ) || exit;

define( 'CDM_VERSION', '1.0.0' );
define( 'CDM_DIR', get_template_directory() );
define( 'CDM_URI', get_template_directory_uri() );

require CDM_DIR . '/inc/helpers.php';
require CDM_DIR . '/inc/customizer.php';
require CDM_DIR . '/inc/post-types.php';
require CDM_DIR . '/inc/tarifs.php';
require CDM_DIR . '/inc/menu.php';
require CDM_DIR . '/inc/demo-content.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus( array(
		'principal' => 'Menu principal',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'cdm-fonts', 'https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Open+Sans:wght@300;400;600&display=swap', array(), null );
	wp_enqueue_style( 'cdm-main', CDM_URI . '/assets/css/main.css', array( 'cdm-fonts' ), CDM_VERSION );
	wp_enqueue_script( 'cdm-main', CDM_URI . '/assets/js/main.js', array(), CDM_VERSION, true );

	wp_register_script( 'cdm-simulateur', CDM_URI . '/assets/js/simulateur.js', array(), CDM_VERSION, true );
	wp_add_inline_script( 'cdm-simulateur', 'window.TARIFS_PENSION = ' . wp_json_encode( cdm_get_tarifs() ) . ';', 'before' );
} );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

// Titre des pages : « Titre — Coeur de Meute »
add_filter( 'document_title_separator', function () {
	return '—';
} );

// Description pour les moteurs de recherche
add_action( 'wp_head', function () {
	if ( is_singular() && has_excerpt() ) {
		$desc = get_the_excerpt();
	} elseif ( is_post_type_archive( 'service' ) ) {
		$desc = 'Éducation canine, pension chiens et chats, prévention morsure, comportement félin, ostéopathie, kinésiologie et garde à domicile à Wanze.';
	} else {
		$desc = get_bloginfo( 'description' );
	}
	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
	}
}, 1 );

// Titre de la page « Nos services »
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_post_type_archive( 'service' ) ) {
		$parts['title'] = 'Nos services';
	}
	return $parts;
} );

// Pas de commentaires sur ce site
add_action( 'init', function () {
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'page', 'comments' );
}, 100 );
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
} );
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
}, 999 );
