<?php
/**
 * Contenus du site : Services, Équipe et Questions fréquentes, avec leurs champs.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'service', array(
		'labels'        => array(
			'name'          => 'Services',
			'singular_name' => 'Service',
			'add_new'       => 'Ajouter un service',
			'add_new_item'  => 'Ajouter un service',
			'edit_item'     => 'Modifier le service',
			'all_items'     => 'Tous les services',
			'menu_name'     => 'Services',
		),
		'public'        => true,
		'has_archive'   => 'services',
		'rewrite'       => array( 'slug' => 'services', 'with_front' => false ),
		'menu_icon'     => 'dashicons-pets',
		'menu_position' => 5,
		'supports'      => array( 'title', 'editor', 'excerpt', 'page-attributes', 'revisions' ),
		'show_in_rest'  => true,
	) );

	register_post_type( 'membre', array(
		'labels'        => array(
			'name'          => 'Équipe',
			'singular_name' => "Membre de l'équipe",
			'add_new'       => 'Ajouter un membre',
			'add_new_item'  => "Ajouter un membre de l'équipe",
			'edit_item'     => 'Modifier le membre',
			'all_items'     => "Toute l'équipe",
		),
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-groups',
		'menu_position' => 6,
		'supports'      => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'faq', array(
		'labels'        => array(
			'name'          => 'Questions fréquentes',
			'singular_name' => 'Question',
			'add_new'       => 'Ajouter une question',
			'add_new_item'  => 'Ajouter une question',
			'edit_item'     => 'Modifier la question',
			'all_items'     => 'Toutes les questions',
			'menu_name'     => 'FAQ',
		),
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-editor-help',
		'menu_position' => 7,
		'supports'      => array( 'title', 'editor', 'page-attributes' ),
		'show_in_rest'  => true,
	) );
} );

// Services et FAQ : éditeur classique, plus simple, avec tous les champs visibles sur une seule page
add_filter( 'use_block_editor_for_post_type', function ( $use, $post_type ) {
	return in_array( $post_type, array( 'service', 'faq' ), true ) ? false : $use;
}, 10, 2 );

// Le résumé sert de texte court sur les cartes : on renomme la boîte pour que ce soit clair
add_filter( 'gettext', function ( $translation, $text ) {
	if ( is_admin() && 'Excerpt' === $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'service' === $screen->post_type ) {
			return 'Texte court (cartes de services)';
		}
		if ( $screen && 'membre' === $screen->post_type ) {
			return 'Présentation courte';
		}
	}
	return $translation;
}, 10, 2 );

/**
 * Champs des services.
 * type : text | textarea | lines | pairs | select | checkbox
 */
function cdm_service_fields() {
	return array(
		'cdm_icon'       => array( 'Icône', 'select', 'paw', wp_list_pluck( cdm_icons(), 0 ) ),
		'cdm_color'      => array( 'Couleur', 'select', 'rust', cdm_colors() ),
		'cdm_pillar'     => array( 'Mettre en avant (« Notre coeur de métier »)', 'checkbox', '' ),
		'cdm_hero_title' => array( 'Titre de la page (si différent du nom)', 'text', '' ),
		'cdm_lead'       => array( 'Phrase sous le titre', 'textarea', '' ),
		'cdm_list_title' => array( 'Titre de la liste', 'text', 'Ce que nous proposons' ),
		'cdm_items'      => array( 'Liste à puces (un point par ligne)', 'lines', '' ),
		'cdm_note'       => array( 'Encadré de remarque (facultatif)', 'textarea', '' ),
		'cdm_facts'      => array( 'Encadré « En bref » (une ligne par info : Titre | Valeur)', 'pairs', '' ),
		'cdm_steps'      => array( 'Étapes « Comment ça se passe ? » (une par ligne : Titre | Texte)', 'pairs', '' ),
		'cdm_simulator'  => array( 'Afficher le simulateur de prix de la pension', 'checkbox', '' ),
		'cdm_pension'    => array( 'Afficher les sections « pension » (vie à la pension, conditions d\'admission)', 'checkbox', '' ),
		'cdm_life'       => array( 'Vie à la pension — 3 cartes (Titre | Texte)', 'pairs', '' ),
		'cdm_adm_dogs'   => array( "Conditions d'admission — chiens (une par ligne)", 'lines', '' ),
		'cdm_adm_cats'   => array( "Conditions d'admission — chats (une par ligne)", 'lines', '' ),
		'cdm_bring'      => array( 'À apporter (un par ligne)', 'lines', '' ),
	);
}

function cdm_membre_fields() {
	return array(
		'cdm_role'  => array( 'Rôle (ex. : Fondatrice)', 'text', '' ),
		'cdm_color' => array( 'Couleur du médaillon', 'select', 'rust', cdm_colors() ),
		'cdm_icon'  => array( 'Médaillon : icône (sinon initiale du nom)', 'select', '', array( '' => 'Initiale du nom' ) + wp_list_pluck( cdm_icons(), 0 ) ),
	);
}

function cdm_page_fields() {
	return array(
		'cdm_hero_title' => array( "Grand titre de l'en-tête (sinon le titre de la page)", 'text', '' ),
		'cdm_lead'       => array( 'Phrase sous le titre', 'textarea', '' ),
	);
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'cdm_page_box', 'En-tête de la page', 'cdm_render_fields_box', 'page', 'normal', 'high', array( 'fields' => cdm_page_fields() ) );
	add_meta_box( 'cdm_service_box', 'Contenu de la page du service', 'cdm_render_fields_box', 'service', 'normal', 'high', array( 'fields' => cdm_service_fields() ) );
	add_meta_box( 'cdm_membre_box', 'Détails', 'cdm_render_fields_box', 'membre', 'normal', 'high', array( 'fields' => cdm_membre_fields() ) );
} );

function cdm_render_fields_box( $post, $box ) {
	wp_nonce_field( 'cdm_save_fields', 'cdm_fields_nonce' );
	echo '<div class="cdm-fields">';
	foreach ( $box['args']['fields'] as $key => $field ) {
		list( $label, $type, $default ) = $field;
		$value = metadata_exists( 'post', $post->ID, $key ) ? get_post_meta( $post->ID, $key, true ) : $default;
		$id    = esc_attr( $key );

		$depends = in_array( $key, array( 'cdm_life', 'cdm_adm_dogs', 'cdm_adm_cats', 'cdm_bring' ), true ) ? ' data-depends="cdm_pension"' : '';
		echo '<p class="cdm-field cdm-field--' . esc_attr( $type ) . '"' . $depends . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( 'checkbox' === $type ) {
			printf( '<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>', $id, checked( $value, '1', false ), esc_html( $label ) );
		} else {
			printf( '<label for="%1$s"><strong>%2$s</strong></label><br>', $id, esc_html( $label ) );
			if ( 'select' === $type ) {
				printf( '<select id="%1$s" name="%1$s">', $id );
				foreach ( $field[3] as $opt => $opt_label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
			} elseif ( 'text' === $type ) {
				printf( '<input type="text" class="widefat" id="%1$s" name="%1$s" value="%2$s">', $id, esc_attr( $value ) );
			} else {
				$rows = 'textarea' === $type ? 2 : 5;
				printf( '<textarea class="widefat" rows="%3$d" id="%1$s" name="%1$s">%2$s</textarea>', $id, esc_textarea( $value ), $rows );
			}
		}
		echo '</p>';
	}
	echo '</div>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['cdm_fields_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cdm_fields_nonce'] ), 'cdm_save_fields' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$all    = array( 'service' => 'cdm_service_fields', 'membre' => 'cdm_membre_fields', 'page' => 'cdm_page_fields' );
	$fields = isset( $all[ $post->post_type ] ) ? call_user_func( $all[ $post->post_type ] ) : array();
	foreach ( $fields as $key => $field ) {
		$type = $field[1];
		if ( 'checkbox' === $type ) {
			update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? '1' : '' );
			continue;
		}
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] );
		if ( 'select' === $type ) {
			$value = array_key_exists( $raw, $field[3] ) ? $raw : $field[2];
		} elseif ( 'text' === $type ) {
			$value = sanitize_text_field( $raw );
		} else {
			$value = wp_kses_post( $raw );
		}
		update_post_meta( $post_id, $key, $value );
	}
}, 10, 2 );

add_action( 'admin_head', function () {
	echo '<style>.cdm-fields .cdm-field{margin:0 0 16px}.cdm-fields .cdm-field--checkbox{margin-bottom:10px}.cdm-fields textarea{font-family:inherit}</style>';
} );

// Les champs « pension » ne s'affichent que si la case correspondante est cochée
add_action( 'admin_footer', function () {
	?>
	<script>
	document.querySelectorAll('.cdm-fields [data-depends]').forEach(function (field) {
		var box = document.getElementById(field.dataset.depends);
		if (!box) return;
		var sync = function () { field.style.display = box.checked ? '' : 'none'; };
		box.addEventListener('change', sync);
		sync();
	});
	</script>
	<?php
} );

// Un nouveau service se place à la fin de la liste
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'service' === $data['post_type'] && empty( $postarr['ID'] ) && 0 === (int) $data['menu_order'] ) {
		global $wpdb;
		$data['menu_order'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type = %s", 'service' ) ) + 1;
	}
	return $data;
}, 10, 2 );
