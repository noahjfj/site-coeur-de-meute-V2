<?php
/**
 * Contenu de départ : créé automatiquement à la première activation du thème
 * (pages, services, équipe, FAQ, galerie, menu), pour retrouver tout de suite le site complet.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_switch_theme', 'cdm_install_demo_content' );

function cdm_demo_services() {
	return array(
		array(
			'slug'    => 'education',
			'title'   => 'Éducation canine',
			'excerpt' => 'Des séances personnalisées, en méthodes bienveillantes, pour construire une relation complice avec votre chien.',
			'content' => '<!-- wp:paragraph -->
<p>Chaque chien est unique, chaque binôme aussi. Elodie, éducatrice canine diplômée, vous accompagne pas à pas pour comprendre votre chien, poser des bases solides et trouver ensemble des solutions adaptées à votre quotidien.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Que vous accueilliez un chiot, que vous adoptiez un chien adulte ou que vous rencontriez une difficulté particulière, les séances sont construites autour de vos objectifs et du rythme de votre compagnon.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'paw',
				'cdm_color' => 'rust',
				'cdm_pillar' => '1',
				'cdm_hero_title' => '',
				'cdm_lead' => 'Construire une relation harmonieuse et complice avec votre chien, grâce à des méthodes positives et bienveillantes.',
				'cdm_list_title' => 'Ce que nous travaillons ensemble',
				'cdm_items' => 'Éducation du chiot et bonnes bases
Rappel, marche en laisse, assis, couché, pas bouger
Sociabilisation avec les autres chiens et les humains
Comportements gênants : aboiements, sauts, tirage…
Accompagnement des chiens craintifs ou réactifs',
				'cdm_note' => '',
				'cdm_facts' => 'Pour qui ? | Chiots et chiens adultes, de toutes races
Format | Séances individuelles, sur rendez-vous
Avec | Elodie Vanrapenbush, éducatrice canine',
				'cdm_steps' => 'Un premier bilan | Nous faisons connaissance avec votre chien et définissons ensemble vos objectifs.
Des séances sur mesure | Exercices concrets, adaptés à votre chien et à votre vie de tous les jours.
Un suivi à la maison | Des conseils simples pour continuer les progrès entre les séances.',
			),
		),
		array(
			'slug'    => 'pension',
			'title'   => 'Pension chiens & chats',
			'excerpt' => 'Un hébergement chaleureux et sécurisé pour votre chien ou votre chat, entouré d\'attention et de soins.',
			'content' => '<!-- wp:paragraph -->
<p>Vacances, week-end, déplacement professionnel ou hospitalisation : votre animal est accueilli au N°28, Chaussée de Wavre, à Wanze, par une équipe attentionnée et formée aux soins.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Les chiens profitent de promenades, de jeux et de moments de repos, dans des groupes formés selon le caractère de chacun. Les chats sont accueillis dans un espace calme, séparé des chiens, où ils vivent à leur rythme.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'house',
				'cdm_color' => 'blue',
				'cdm_pillar' => '1',
				'cdm_hero_title' => 'Pension pour chiens et chats',
				'cdm_lead' => 'Des vacances aussi pour votre compagnon, dans un cadre pensé pour son bien-être et sa sécurité.',
				'cdm_list_title' => 'Ce qui nous tient à coeur',
				'cdm_items' => 'Respect de son alimentation et de ses habitudes
Groupes formés selon le caractère de chaque chien
Espace calme et séparé pour les chats
Suivi par une auxiliaire de santé vétérinaire
Vétérinaire référent : Dr Julien Joiret',
				'cdm_note' => '',
				'cdm_facts' => 'Pour qui ? | Chiens et chats
Accueil | Tous les jours de 8h30 à 18h, heure convenue au préalable',
				'cdm_steps' => 'On fait connaissance | Un premier rendez-vous pour visiter les lieux et nous parler de votre compagnon : habitudes, caractère, santé.
On fixe l\'heure d\'arrivée | Ensemble, nous convenons à l\'avance du jour et de l\'heure de dépôt et de reprise de votre compagnon.
Partez l\'esprit léger | Pendant son séjour, nous prenons soin de lui comme du nôtre et vous donnons de ses nouvelles.',
				'cdm_simulator' => '1',
				'cdm_pension' => '1',
				'cdm_life' => 'Promenades & jeux | Des sorties et des moments de jeu adaptés à l\'énergie et au caractère de chaque chien.
Repas & habitudes | Nous respectons son alimentation et ses routines pour que son séjour se passe en douceur.
Repos & câlins | Des espaces calmes pour se reposer et beaucoup d\'attention pour se sentir comme à la maison.',
				'cdm_adm_dogs' => 'Vaccins à jour (carnet de vaccination)
Identification par puce électronique
Traitement antiparasitaire récent (puces, tiques)
Vermifuge à jour',
				'cdm_adm_cats' => 'Vaccins à jour (carnet de vaccination)
Identification par puce électronique
Traitement antiparasitaire récent
Vermifuge à jour',
				'cdm_bring' => 'Sa nourriture habituelle
Son carnet de vaccination
Ses éventuels médicaments
Un doudou ou une couverture qui sent la maison',
			),
		),
		array(
			'slug'    => 'prevention-morsure',
			'title'   => 'Prévention morsure',
			'excerpt' => 'Apprendre à lire le langage du chien et adopter les bons gestes pour éviter les accidents, en famille.',
			'content' => '<!-- wp:paragraph -->
<p>La plupart des morsures surviennent avec un chien connu, souvent à la maison. Elles peuvent pourtant être évitées en apprenant à reconnaître les signaux qu\'un chien envoie lorsqu\'il est mal à l\'aise.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nous vous aidons à décoder son langage corporel et à mettre en place des règles simples pour que petits et grands cohabitent en toute sécurité.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'shield',
				'cdm_color' => 'sand',
				'cdm_pillar' => '',
				'cdm_hero_title' => '',
				'cdm_lead' => 'Comprendre le chien pour éviter les accidents : un enjeu essentiel, surtout lorsqu\'il y a des enfants à la maison.',
				'cdm_list_title' => 'Au programme',
				'cdm_items' => 'Lire le langage corporel et les signaux d\'inconfort
Les bons gestes à apprendre aux enfants
Aménager la maison pour le confort de chacun
Préparer l\'arrivée d\'un bébé ou d\'un nouveau chien',
				'cdm_note' => '',
				'cdm_facts' => 'Pour qui ? | Familles, parents, enfants et futurs adoptants
Format | Sur rendez-vous
Groupes | Demandes particulières : contactez-nous',
				'cdm_steps' => 'On échange | Vous nous parlez de votre famille, de votre chien et de vos questions.
On apprend ensemble | Explications concrètes, exemples et mises en situation adaptées à l\'âge des enfants.
Vous repartez outillés | Des repères simples pour une cohabitation sereine au quotidien.',
			),
		),
		array(
			'slug'    => 'comportement-felin',
			'title'   => 'Comportement félin',
			'excerpt' => 'Comprendre votre chat pour résoudre malpropreté, griffades, stress ou conflits et retrouver une cohabitation sereine.',
			'content' => '<!-- wp:paragraph -->
<p>Le chat est un animal sensible, très attaché à son territoire et à ses habitudes. Un changement, même léger, peut se traduire par des comportements qui posent problème au quotidien.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nous analysons avec vous son environnement, son histoire et ses habitudes pour comprendre l\'origine du problème et vous proposer des solutions concrètes et respectueuses de ses besoins.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'cat',
				'cdm_color' => 'blue',
				'cdm_pillar' => '',
				'cdm_hero_title' => '',
				'cdm_lead' => 'Comprendre votre chat pour retrouver une cohabitation sereine.',
				'cdm_list_title' => 'Nous pouvons vous aider en cas de',
				'cdm_items' => 'Malpropreté ou marquage urinaire
Griffades sur le mobilier
Agressivité, peurs ou stress
Cohabitation difficile entre chats ou avec un chien
Déménagement, arrivée d\'un bébé ou d\'un nouvel animal',
				'cdm_note' => '',
				'cdm_facts' => 'Pour qui ? | Chats de tous âges
Format | Sur rendez-vous',
				'cdm_steps' => 'Un bilan complet | Un échange approfondi sur votre chat, son environnement et ses habitudes.
Un plan d\'action | Des aménagements et des conseils concrets, adaptés à votre foyer.
Un suivi | Nous restons à vos côtés pour ajuster si nécessaire.',
			),
		),
		array(
			'slug'    => 'osteopathie',
			'title'   => 'Ostéopathie',
			'excerpt' => 'Une approche manuelle et douce pour soulager les tensions et favoriser la mobilité et le confort de votre animal.',
			'content' => '<!-- wp:paragraph -->
<p>L\'ostéopathie s\'intéresse au corps dans son ensemble. Par des techniques manuelles douces, elle vise à relâcher les tensions et à améliorer la mobilité, pour que votre compagnon se sente mieux dans son corps.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Elle s\'adresse aux chiens comme aux chats, à tout âge, en complément du suivi assuré par votre vétérinaire.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'spine',
				'cdm_color' => 'brown',
				'cdm_pillar' => '',
				'cdm_hero_title' => 'Ostéopathie animale',
				'cdm_lead' => 'Une approche manuelle et douce pour soulager les tensions et favoriser le confort de votre animal.',
				'cdm_list_title' => 'Quand y penser ?',
				'cdm_items' => 'Après une chute, un choc ou un effort intense
Raideurs, gêne à la marche ou au saut
Animal âgé qui se déplace moins bien
Chien sportif ou très actif
Changement de comportement pouvant être lié à un inconfort',
				'cdm_note' => 'L\'ostéopathie est complémentaire et ne remplace pas une consultation vétérinaire. En cas de douleur ou de boiterie, consultez d\'abord votre vétérinaire.',
				'cdm_facts' => 'Pour qui ? | Chiens et chats, à tout âge
Format | Sur rendez-vous',
				'cdm_steps' => 'On fait le point | Historique, habitudes et observation de votre animal en mouvement.
La séance | Des manipulations douces, dans le respect de son confort.
Les conseils | Des recommandations pour prolonger les bienfaits à la maison.',
			),
		),
		array(
			'slug'    => 'kinesiologie',
			'title'   => 'Kinésiologie',
			'excerpt' => 'Une approche douce pour accompagner l\'équilibre émotionnel de votre animal face au stress et aux changements.',
			'content' => '<!-- wp:paragraph -->
<p>La kinésiologie est une approche de bien-être qui vise à aider l\'animal à retrouver son équilibre lorsqu\'il traverse une période de stress, de peur ou de changement.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Elle peut aussi venir en soutien d\'un travail d\'éducation ou comportemental, pour avancer plus sereinement.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'wave',
				'cdm_color' => 'sand',
				'cdm_pillar' => '',
				'cdm_hero_title' => 'Kinésiologie animale',
				'cdm_lead' => 'Une approche douce pour accompagner l\'équilibre émotionnel de votre animal.',
				'cdm_list_title' => 'Quand y penser ?',
				'cdm_items' => 'Stress, anxiété ou hyperactivité
Peurs (bruits, voiture, visiteurs…)
Changements de vie : déménagement, adoption, séparation
En soutien d\'un travail d\'éducation ou de comportement',
				'cdm_note' => 'La kinésiologie est une approche de bien-être complémentaire. Elle ne remplace pas un avis ou un traitement vétérinaire.',
				'cdm_facts' => 'Pour qui ? | Chiens et chats
Format | Sur rendez-vous',
				'cdm_steps' => 'On échange | Vous nous parlez de votre animal et de ce qu\'il traverse.
La séance | Un moment calme et respectueux, à son rythme.
Le suivi | Des conseils pour l\'accompagner au quotidien.',
			),
		),
		array(
			'slug'    => 'garde-a-domicile',
			'title'   => 'Garde à domicile',
			'excerpt' => 'Votre animal reste chez lui, dans ses habitudes : c\'est nous qui passons pour les repas, les câlins et les soins.',
			'content' => '<!-- wp:paragraph -->
<p>Certains animaux sont plus sereins dans leur environnement : animaux âgés, anxieux, ou tout simplement attachés à leur petit confort. Avec la garde à domicile, ils gardent leurs repères pendant votre absence.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nous passons chez vous pour prendre soin d\'eux et vous donnons de leurs nouvelles.</p>
<!-- /wp:paragraph -->',
			'meta'    => array(
				'cdm_icon' => 'homeheart',
				'cdm_color' => 'rust',
				'cdm_pillar' => '',
				'cdm_hero_title' => '',
				'cdm_lead' => 'Votre animal reste tranquillement chez lui : c\'est nous qui passons.',
				'cdm_list_title' => 'Pendant nos visites',
				'cdm_items' => 'Repas, eau fraîche et litière
Promenades, jeux et câlins
Administration des soins si nécessaire
Des nouvelles régulières',
				'cdm_note' => '',
				'cdm_facts' => 'Pour qui ? | Chiens et chats
Format | Visites sur rendez-vous
Zone | Modalités et zone de déplacement sur demande',
				'cdm_steps' => 'On se rencontre | Une visite chez vous pour faire connaissance avec votre animal et ses habitudes.
On organise | Nous convenons ensemble du nombre et de l\'horaire des visites.
Vous partez sereins | Votre compagnon garde ses repères et vous recevez de ses nouvelles.',
			),
		),
	);
}

function cdm_demo_block_p( $text, $class = '' ) {
	$attrs = $class ? ' {"className":"' . $class . '"}' : '';
	$cls   = $class ? ' class="' . $class . '"' : '';
	return "<!-- wp:paragraph{$attrs} -->\n<p{$cls}>{$text}</p>\n<!-- /wp:paragraph -->";
}

function cdm_import_theme_image( $file, $title ) {
	$existing = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_cdm_source', 'meta_value' => $file, 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $existing ) {
		return $existing[0];
	}
	$path = CDM_DIR . '/assets/img/' . $file;
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	$upload = wp_upload_bits( $file, null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = wp_insert_attachment( array(
		'post_title'     => $title,
		'post_mime_type' => wp_check_filetype( $upload['file'] )['type'],
		'post_status'    => 'inherit',
	), $upload['file'] );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_cdm_source', $file );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return $id;
}

function cdm_demo_page( $slug, $title, $content = '', $meta = array() ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return $page->ID;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
	) );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return $id;
}

function cdm_install_demo_content() {
	if ( get_option( 'cdm_demo_installed' ) ) {
		return;
	}

	// Réglages généraux
	update_option( 'blogname', 'Coeur de Meute' );
	update_option( 'blogdescription', 'Centre pluridisciplinaire pour chiens et chats à Wanze' );
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	$photo = cdm_import_theme_image( 'devanture.jpg', "L'équipe de Coeur de Meute devant la pension" );

	// Services
	$service_ids = array();
	if ( ! get_posts( array( 'post_type' => 'service', 'posts_per_page' => 1, 'post_status' => 'any' ) ) ) {
		foreach ( cdm_demo_services() as $order => $s ) {
			$id = wp_insert_post( array(
				'post_type'    => 'service',
				'post_status'  => 'publish',
				'post_name'    => $s['slug'],
				'post_title'   => $s['title'],
				'post_excerpt' => $s['excerpt'],
				'post_content' => $s['content'],
				'menu_order'   => $order,
			) );
			foreach ( $s['meta'] as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}
			$service_ids[ $s['slug'] ] = $id;
		}
	}

	// Pages
	$home = cdm_demo_page( 'accueil', 'Accueil' );

	$qui = cdm_demo_page( 'qui-sommes-nous', 'Qui sommes-nous ?', implode( "\n\n", array(
		cdm_demo_block_p( 'La fondatrice', 'eyebrow' ),
		"<!-- wp:heading {\"className\":\"title\"} -->\n<h2 class=\"wp-block-heading title\">Elodie Vanrapenbush</h2>\n<!-- /wp:heading -->",
		cdm_demo_block_p( "Éducatrice canine et auxiliaire de santé vétérinaire, Elodie a fondé Coeur de Meute avec une conviction simple&nbsp;: un animal bien entouré est un animal épanoui." ),
		cdm_demo_block_p( "Sa double formation lui permet de comprendre à la fois le comportement de votre compagnon et ses besoins en matière de soins. Chaque animal est accueilli avec attention, dans le respect de son caractère et de son rythme." ),
		"<!-- wp:list {\"className\":\"checklist\"} -->\n<ul class=\"wp-block-list checklist\"><!-- wp:list-item -->\n<li>Éducatrice canine diplômée</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Auxiliaire de santé vétérinaire</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Méthodes d'éducation bienveillantes</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
	) ), array(
		'cdm_hero_title' => 'Une équipe passionnée, au coeur de la meute',
		'cdm_lead'       => "Coeur de Meute est un centre pluridisciplinaire&nbsp;: une équipe aux compétences complémentaires, réunie autour d'un même amour des animaux.",
	) );
	if ( $photo && ! has_post_thumbnail( $qui ) ) {
		set_post_thumbnail( $qui, $photo );
	}

	$gallery = $photo ? sprintf(
		"<!-- wp:gallery {\"linkTo\":\"media\",\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-gallery has-nested-images columns-default is-cropped\"><!-- wp:image {\"id\":%1\$d,\"sizeSlug\":\"large\",\"linkDestination\":\"media\"} -->\n<figure class=\"wp-block-image size-large\"><a href=\"%2\$s\"><img src=\"%3\$s\" alt=\"L'équipe de Coeur de Meute devant la pension\" class=\"wp-image-%1\$d\"/></a><figcaption class=\"wp-element-caption\">Notre équipe devant la pension</figcaption></figure>\n<!-- /wp:image --></figure>\n<!-- /wp:gallery -->",
		$photo,
		esc_url( wp_get_attachment_url( $photo ) ),
		esc_url( wp_get_attachment_image_url( $photo, 'large' ) )
	) : '';
	cdm_demo_page( 'galerie', 'Galerie', $gallery, array(
		'cdm_hero_title' => 'La vie de la meute en images',
		'cdm_lead'       => 'Nos pensionnaires, nos locaux et nos moments de partage.',
	) );

	cdm_demo_page( 'a-propos', 'À propos', '', array(
		'cdm_hero_title' => 'Informations pratiques',
		'cdm_lead'       => "Où nous trouver, comment nous contacter et les réponses aux questions que l'on nous pose souvent.",
	) );

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );

	// Équipe
	if ( ! get_posts( array( 'post_type' => 'membre', 'posts_per_page' => 1, 'post_status' => 'any' ) ) ) {
		$team = array(
			array( 'Elodie Vanrapenbush', 'Fondatrice', 'Éducatrice canine et auxiliaire de santé vétérinaire.', 'rust', '' ),
			array( 'Notre équipe', 'Soigneuses', 'Des passionnées présentes au quotidien pour les promenades, les jeux, les repas et les câlins.', 'sand', 'paw' ),
			array( 'Dr Julien Joiret', 'Vétérinaire référent', 'Notre partenaire santé, disponible pour le suivi médical de nos pensionnaires.', 'blue', 'cross' ),
			array( 'Nos intervenants', 'Bien-être & comportement', 'Ostéopathie, kinésiologie et comportement félin : des spécialistes pour prendre soin du corps et des émotions.', 'brown', 'wave' ),
		);
		foreach ( $team as $order => $m ) {
			$id = wp_insert_post( array( 'post_type' => 'membre', 'post_status' => 'publish', 'post_title' => $m[0], 'post_excerpt' => $m[2], 'menu_order' => $order ) );
			update_post_meta( $id, 'cdm_role', $m[1] );
			update_post_meta( $id, 'cdm_color', $m[3] );
			update_post_meta( $id, 'cdm_icon', $m[4] );
		}
	}

	// Questions fréquentes
	if ( ! get_posts( array( 'post_type' => 'faq', 'posts_per_page' => 1, 'post_status' => 'any' ) ) ) {
		$url  = function ( $slug ) use ( $service_ids ) {
			return isset( $service_ids[ $slug ] ) ? get_permalink( $service_ids[ $slug ] ) : home_url( '/services/' . $slug . '/' );
		};
		$link = function ( $slug, $label ) use ( $url ) {
			return '<a href="' . esc_url( $url( $slug ) ) . '">' . $label . '</a>';
		};
		$faqs = array(
			array( 'Quels services proposez-vous ?', "Coeur de Meute est un centre pluridisciplinaire. Notre coeur de métier est l'" . $link( 'education', 'éducation canine' ) . ' et la ' . $link( 'pension', 'pension pour chiens et chats' ) . '. Nous proposons aussi la ' . $link( 'prevention-morsure', 'prévention morsure' ) . ', le ' . $link( 'comportement-felin', 'comportement félin' ) . ", l'" . $link( 'osteopathie', 'ostéopathie' ) . ', la ' . $link( 'kinesiologie', 'kinésiologie' ) . ' et la ' . $link( 'garde-a-domicile', 'garde à domicile' ) . '.' ),
			array( 'Faut-il prendre rendez-vous ?', "Oui. Toute rencontre, que ce soit pour la pension ou pour l'éducation, se fait uniquement sur rendez-vous. Appelez-nous au 0455 / 10.97.97." ),
			array( 'À quelle heure puis-je déposer et reprendre mon animal ?', "L'accueil peut se faire tous les jours entre 8h30 et 18h, sauf exceptions. L'heure exacte est convenue ensemble au préalable. En cas de retard, un petit coup de fil pour nous prévenir suffit." ),
			array( 'Accueillez-vous aussi les chats ?', 'Oui ! Les chats sont accueillis dans un espace calme, séparé des chiens.' ),
			array( 'Que dois-je apporter pour le séjour ?', "Sa nourriture habituelle, son carnet de vaccination, ses éventuels médicaments et, s'il en a un, un doudou ou une couverture qui sent la maison." ),
			array( 'Et si mon animal a besoin de soins pendant son séjour ?', 'Elodie est auxiliaire de santé vétérinaire et nous travaillons avec un vétérinaire référent, le Dr Julien Joiret. Votre compagnon est entre de bonnes mains.' ),
			array( 'Proposez-vous une garde à domicile ?', "Oui. Votre animal reste chez lui, dans ses habitudes, et c'est nous qui passons pour les repas, les promenades, les câlins et les soins." ),
		);
		foreach ( $faqs as $order => $f ) {
			wp_insert_post( array( 'post_type' => 'faq', 'post_status' => 'publish', 'post_title' => $f[0], 'post_content' => $f[1], 'menu_order' => $order ) );
		}
	}

	// Menu principal
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['principal'] ) ) {
		$menu_id = wp_create_nav_menu( 'Menu principal' );
		if ( ! is_wp_error( $menu_id ) ) {
			$add_page = function ( $page_id, $title ) use ( $menu_id ) {
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				) );
			};
			$add_page( $home, 'Accueil' );
			$add_page( $qui, 'Qui sommes-nous' );
			// « Nos services » : la liste des services s'affiche automatiquement en sous-menu
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => 'Nos services',
				'menu-item-type'   => 'post_type_archive',
				'menu-item-object' => 'service',
				'menu-item-status' => 'publish',
			) );
			$add_page( get_page_by_path( 'galerie' )->ID, 'Galerie' );
			$add_page( get_page_by_path( 'a-propos' )->ID, 'À propos' );
			$locations['principal'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	update_option( 'cdm_demo_installed', 1 );
	// Les adresses des services sont recalculées au prochain chargement,
	// une fois le nouveau format d'adresse pris en compte.
	update_option( 'cdm_flush_rewrite', 1 );
}

add_action( 'init', function () {
	if ( get_option( 'cdm_flush_rewrite' ) ) {
		delete_option( 'cdm_flush_rewrite' );
		flush_rewrite_rules();
	}
}, 99 );

// Changement de thème : les adresses des services sont aussi recalculées
add_action( 'after_switch_theme', function () {
	update_option( 'cdm_flush_rewrite', 1 );
}, 20 );
