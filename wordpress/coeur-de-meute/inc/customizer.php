<?php
/**
 * Personnalisateur (Apparence → Personnaliser) : coordonnées, horaires et textes du site.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sections et champs du Personnalisateur.
 * type : text | textarea | image
 */
function cdm_customizer_fields() {
	return array(
		'cdm_contact' => array(
			'title'  => 'Coordonnées',
			'fields' => array(
				'phone'          => array( 'Téléphone', 'text', '0455 / 10.97.97' ),
				'email'          => array( 'E-mail', 'text', 'coeurdemeute1@gmail.com' ),
				'address_street' => array( 'Adresse — rue', 'text', 'N°28, Chaussée de Wavre' ),
				'address_city'   => array( 'Adresse — code postal et ville', 'text', '4520 Wanze' ),
				'address_country' => array( 'Pays', 'text', 'Belgique' ),
				'vet'            => array( 'Vétérinaire référent', 'text', 'Dr Julien Joiret' ),
			),
		),
		'cdm_hours'   => array(
			'title'  => 'Horaires',
			'fields' => array(
				'hours_days'   => array( 'Jours', 'text', 'Tous les jours' ),
				'hours_time'   => array( 'Heures', 'text', 'de 8h30 à 18h' ),
				'hours_note'   => array( 'Précision', 'textarea', "Sauf exceptions. L'heure d'arrivée et de départ de votre compagnon est convenue ensemble au préalable." ),
				'note_rdv'     => array( 'Encadré « Sur rendez-vous »', 'textarea', "toute rencontre, que ce soit pour la pension ou l'éducation, se fait uniquement sur rendez-vous." ),
				'note_retard'  => array( 'Encadré « Un petit retard ? »', 'textarea', 'Ça arrive à tout le monde ! Un simple coup de fil pour nous prévenir, et votre compagnon vous attendra sereinement.' ),
			),
		),
		'cdm_hero'    => array(
			'title'  => 'Accueil — bannière',
			'fields' => array(
				'hero_kicker' => array( 'Texte au-dessus du nom', 'text', 'Pension & Éducation canine' ),
				'hero_slogan' => array( 'Slogan (séparer par ·)', 'text', 'Sérénité · Sécurité · Santé' ),
				'hero_button' => array( 'Texte du bouton', 'text', 'Réserver un séjour' ),
			),
		),
		'cdm_intro'   => array(
			'title'  => 'Accueil — bienvenue',
			'fields' => array(
				'welcome'     => array( 'Grand titre', 'text', 'Bienvenue' ),
				'intro_title' => array( 'Sous-titre', 'text', 'Un second foyer pour votre compagnon' ),
				'intro_text'  => array( 'Texte (gras : <strong>…</strong>)', 'textarea', "Située en plein <strong>centre de Wanze</strong>, <strong>Coeur de Meute</strong> est un centre pluridisciplinaire pour chiens et chats. Au coeur de notre métier&nbsp;: <strong>l'éducation canine</strong> et <strong>la pension</strong>, complétées par la prévention morsure, le comportement félin, l'ostéopathie, la kinésiologie et la garde à domicile. Votre compagnon est accueilli comme un membre de la famille, avec bienveillance, patience et professionnalisme." ),
			),
		),
		'cdm_about'   => array(
			'title'  => 'Accueil — qui sommes-nous',
			'fields' => array(
				'about_image' => array( 'Photo', 'image', '' ),
				'about_title' => array( 'Titre', 'text', 'Une équipe passionnée, au coeur de la meute' ),
				'about_text'  => array( 'Texte (un paragraphe par ligne)', 'textarea', "Fondée par <strong>Elodie Vanrapenbush</strong>, éducatrice canine et auxiliaire de santé vétérinaire, Coeur de Meute est née d'une conviction simple : un animal bien entouré est un animal épanoui.\nEntourée d'une équipe attentionnée, Elodie met ses compétences en comportement et en soins au service de votre animal. Chaque séjour commence par une rencontre afin de connaître ses habitudes, son caractère et ses besoins." ),
				'about_list'  => array( 'Points forts (un par ligne)', 'textarea', "Éducatrice canine diplômée\nAuxiliaire de santé vétérinaire\nVétérinaire référent : Dr Julien Joiret" ),
			),
		),
		'cdm_values'  => array(
			'title'  => 'Nos valeurs (page Qui sommes-nous)',
			'fields' => array(
				'value1_title' => array( 'Valeur 1 — titre', 'text', 'Sérénité' ),
				'value1_text'  => array( 'Valeur 1 — texte', 'textarea', 'Un cadre calme et rassurant, des routines respectées et une attention de chaque instant pour que votre compagnon se sente chez lui.' ),
				'value2_title' => array( 'Valeur 2 — titre', 'text', 'Sécurité' ),
				'value2_text'  => array( 'Valeur 2 — texte', 'textarea', 'Des espaces adaptés et sécurisés, des groupes formés selon le caractère de chacun et une surveillance constante.' ),
				'value3_title' => array( 'Valeur 3 — titre', 'text', 'Santé' ),
				'value3_text'  => array( 'Valeur 3 — texte', 'textarea', 'Un suivi assuré par une auxiliaire de santé vétérinaire, en lien avec notre vétérinaire référent, le Dr Julien Joiret.' ),
			),
		),
		'cdm_footer'  => array(
			'title'  => 'Pied de page & bandeau de contact',
			'fields' => array(
				'footer_text' => array( 'Texte sous le logo (une ligne par ligne)', 'textarea', "Centre pluridisciplinaire\npour chiens & chats\nÉducation · Pension · Bien-être" ),
				'cta_title'   => array( 'Bandeau — titre', 'text', 'Envie de nous rencontrer ?' ),
				'cta_text'    => array( 'Bandeau — texte', 'textarea', "Toute rencontre, pour la pension comme pour l'éducation, se fait sur rendez-vous. Un appel suffit !" ),
			),
		),
	);
}

function cdm_customizer_defaults() {
	static $defaults = null;
	if ( null === $defaults ) {
		$defaults = array();
		foreach ( cdm_customizer_fields() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				$defaults[ $key ] = $field[2];
			}
		}
	}
	return $defaults;
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_panel( 'cdm_panel', array(
		'title'    => 'Contenus Coeur de Meute',
		'priority' => 20,
	) );

	$priority = 10;
	foreach ( cdm_customizer_fields() as $section_id => $section ) {
		$wp_customize->add_section( $section_id, array(
			'title'    => $section['title'],
			'panel'    => 'cdm_panel',
			'priority' => $priority += 10,
		) );

		foreach ( $section['fields'] as $key => $field ) {
			list( $label, $type, $default ) = $field;

			if ( 'image' === $type ) {
				$wp_customize->add_setting( $key, array( 'default' => $default, 'sanitize_callback' => 'absint' ) );
				$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $key, array(
					'label'     => $label,
					'section'   => $section_id,
					'mime_type' => 'image',
				) ) );
				continue;
			}

			$wp_customize->add_setting( $key, array(
				'default'           => $default,
				'sanitize_callback' => 'textarea' === $type ? 'wp_kses_post' : 'sanitize_text_field',
			) );
			$wp_customize->add_control( $key, array(
				'label'   => $label,
				'section' => $section_id,
				'type'    => $type,
			) );
		}
	}
} );
