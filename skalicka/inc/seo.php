<?php
/**
 * SEO: titulky a popisky stránek pro Google, náhled odkazu pro Facebook/WhatsApp (Open Graph),
 * strukturovaná data o herně (adresa, otevírací doba) a úklid mapy webu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Klíč stránky = její adresa bez lomítek, úvod = „/“. */
function skalicka_seo_key() {
	if ( is_front_page() ) {
		return '/';
	}
	if ( is_page() ) {
		return get_page_uri( get_queried_object_id() );
	}
	return '';
}

/** Titulky (před „– Dětský svět Skalička“). Prázdné = nechá název stránky. */
function skalicka_seo_titles() {
	return array(
		'herna-a-vstupne'                => 'Herna a vstupné – dětská herna v Příbrami',
		'herna-a-vstupne/kavarna-a-menu' => 'Kavárna a menu v herně',
		'clenstvi'                       => 'Členství v herně – neomezený vstup',
		'lekce'                          => 'Lekce pro děti – Pohybovky 3–6 let',
		'oslavy'                         => 'Dětské oslavy a narozeniny v Příbrami',
		'navsteva'                       => 'Otevírací doba a cesta do herny',
		'skolky-a-skoly'                 => 'Výlety do herny pro školky a školy',
	);
}

/** Otevírací doba jedním slovem pro popisek, např. „denně 9:00–18:30“. */
function skalicka_seo_hours_text() {
	$h = array_unique( array_filter( skalicka_hours() ) );
	if ( 1 === count( $h ) && 7 === count( array_filter( skalicka_hours() ) ) ) {
		list( $od, $do ) = explode( '-', reset( $h ) );
		return 'denně ' . ltrim( $od, '0' ) . '–' . ltrim( $do, '0' );
	}
	return '';
}

/** Popisky pro výsledky vyhledávání (do 160 znaků). */
function skalicka_seo_descriptions() {
	$hod = skalicka_seo_hours_text();
	return array(
		'/'                              => 'Dětská herna s kavárnou v OC Skalka, Příbram. Přes 300 m² hraní pro děti od miminek do 13 let' . ( $hod ? ', ' . $hod : '' ) . '. Vstup 150 Kč na celý den.',
		'herna-a-vstupne'                => 'Vstupné 150 Kč za dítě na celý den, miminka do 6 měsíců a doprovod zdarma. Velká prolézačka, zóna pro batolata, lezecká stěna a kavárna s výhledem na hernu.',
		'herna-a-vstupne/kavarna-a-menu' => 'Káva, domácí limonády, vafle s ovocem a toasty v kavárně přímo v herně. Sedíte v klidu a děti máte na očích. Členové mají 10 % z celého účtu.',
		'clenstvi'                       => 'Členství v herně Skalička: neomezený vstup pro dítě, lekce a tematické akce zdarma, 10 % v kavárně i z ceny oslavy. Platba kartou online, zrušíte kdykoli.',
		'lekce'                          => 'Pohybovky pro děti 3–6 let v Příbrami: tanec, dětská jóga a pohybové hry. 45 minut v malé skupině do 8 dětí, 150 Kč i se vstupem do herny na celý den.',
		'oslavy'                         => 'Dětské narozeniny v herně v OC Skalka, Příbram. Stůl pro 8–10 dětí, vlastní salonek pro 12 nebo celá herna. Připravíme a vyzdobíme, vy jen přijdete.',
		'navsteva'                       => 'Otevírací doba, cesta a parkování v OC Skalka. Herna je ve 3. patře, první hodina v garážích je zdarma a další vám prodloužíme na pokladně.',
		'skolky-a-skoly'                 => 'Výlet do herny pro školky a školy z Příbrami a okolí: 70 Kč za dítě, pedagogický doprovod zdarma, všední dny dopoledne, skupiny do 25 dětí.',
		'kontakt'                        => 'Dětský svět Skalička, OC Skalka, 3. patro, Milínská 134, Příbram. Zavolejte, napište e-mail nebo nám pošlete zprávu přímo přes formulář na webu.',
	);
}

/** Popisek aktuální stránky: z tabulky výše, jinak z úryvku nebo začátku textu. */
function skalicka_seo_description() {
	$key = skalicka_seo_key();
	$map = skalicka_seo_descriptions();
	if ( '' !== $key && isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}
	if ( is_singular() ) {
		$p    = get_queried_object();
		$text = has_excerpt( $p ) ? $p->post_excerpt : $p->post_content;
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );
		if ( $text ) {
			return mb_strlen( $text ) > 158 ? rtrim( mb_substr( $text, 0, 155 ), " ,.;:–-" ) . '…' : $text;
		}
	}
	return get_bloginfo( 'description' );
}

add_filter( 'document_title_parts', function ( $parts ) {
	$key = skalicka_seo_key();
	$map = skalicka_seo_titles();
	if ( '' !== $key && ! empty( $map[ $key ] ) ) {
		$parts['title'] = $map[ $key ];
	}
	return $parts;
} );

/** Obrázek pro sdílení: náhledový obrázek stránky, jinak obecný obrázek herny. */
function skalicka_seo_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $src && $src[1] >= 600 ) {
			return array( $src[0], $src[1], $src[2] );
		}
	}
	$id = (int) get_option( 'skalicka_og_image_id_v2' );
	if ( ! $id ) {
		$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'name' => 'skalicka-sdileni', 'numberposts' => 1, 'fields' => 'ids' ) );
		$id    = $found ? (int) $found[0] : 0;
		if ( $id ) {
			update_option( 'skalicka_og_image_id_v2', $id, true );
		}
	}
	if ( $id > 0 ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( $src ) {
			return array( $src[0], $src[1], $src[2] );
		}
	}
	return array();
}

add_action( 'wp_head', function () {
	if ( is_admin() || is_404() ) {
		return;
	}
	$desc  = skalicka_seo_description();
	$title = wp_get_document_title();
	$url   = is_front_page() ? home_url( '/' ) : ( is_singular() ? get_permalink() : '' );
	$img   = skalicka_seo_image();

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $url ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
	echo '<meta property="og:locale" content="cs_CZ">' . "\n";
	echo '<meta property="og:type" content="' . ( is_front_page() ? 'website' : 'article' ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $url ) {
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	}
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img[0] ) . '">' . "\n";
		echo '<meta property="og:image:width" content="' . (int) $img[1] . '">' . "\n";
		echo '<meta property="og:image:height" content="' . (int) $img[2] . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}, 3 );

/* WordPress si canonical vypisuje sám – necháme jen ten náš. */
remove_action( 'wp_head', 'rel_canonical' );

/** Strukturovaná data pro Google: herna jako místní podnik s adresou a otevírací dobou. */
function skalicka_seo_schema() {
	$lines = array_values( array_filter( array_map( 'trim', explode( "\n", (string) skalicka_opt( 'address' ) ) ) ) );
	$last  = end( $lines );
	$psc   = '';
	$mesto = '';
	$ulice = '';
	foreach ( $lines as $l ) {
		if ( preg_match( '/^(.*?),\s*(\d{3}\s?\d{2})\s+(.+)$/u', $l, $m ) ) {
			$ulice = $m[1];
			$psc   = $m[2];
			$mesto = $m[3];
		}
	}
	$days  = array( 1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
	$hours = array();
	foreach ( skalicka_hours() as $d => $v ) {
		if ( $v ) {
			list( $od, $do ) = explode( '-', $v );
			$hours[]         = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $days[ $d ],
				'opens'     => $od,
				'closes'    => $do,
			);
		}
	}
	$img  = skalicka_seo_image();
	$data = array(
		'@context'                  => 'https://schema.org',
		'@type'                     => 'EntertainmentBusiness',
		'@id'                       => home_url( '/#herna' ),
		'name'                      => get_bloginfo( 'name' ),
		'description'               => skalicka_seo_descriptions()['/'],
		'url'                       => home_url( '/' ),
		'telephone'                 => skalicka_opt( 'phone' ),
		'email'                     => skalicka_opt( 'email' ),
		'priceRange'                => '150 Kč',
		'currenciesAccepted'        => 'CZK',
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $ulice ? $ulice . ( count( $lines ) > 1 ? ' (' . $lines[0] . ')' : '' ) : $last,
			'postalCode'      => $psc,
			'addressLocality' => $mesto,
			'addressCountry'  => 'CZ',
		),
		'hasMap'                    => skalicka_opt( 'map_url' ),
		'openingHoursSpecification' => $hours,
		'sameAs'                    => array_values( array_filter( array( skalicka_opt( 'fb_url' ), skalicka_opt( 'ig_url' ) ) ) ),
	);
	if ( $img ) {
		$data['image'] = $img[0];
	}
	$logo = get_site_icon_url( 512 );
	if ( $logo ) {
		$data['logo'] = $logo;
	}
	return array_filter( $data );
}

add_action( 'wp_head', function () {
	if ( is_front_page() || ( is_page() && in_array( skalicka_seo_key(), array( 'kontakt', 'navsteva' ), true ) ) ) {
		echo '<script type="application/ld+json">' . wp_json_encode( skalicka_seo_schema(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 4 );

/* Mapa webu: bez uživatelů (neprozrazovat přihlašovací jména), bez prázdných kategorií a blogu. */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies', function ( $tax ) {
	unset( $tax['category'], $tax['post_tag'], $tax['post_format'] );
	return $tax;
} );
add_filter( 'wp_sitemaps_post_types', function ( $types ) {
	if ( ! wp_count_posts( 'post' )->publish || 1 >= wp_count_posts( 'post' )->publish ) {
		unset( $types['post'] );
	}
	return $types;
} );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$d = get_page_by_path( 'clenstvi/dekujeme' );
		if ( $d ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), array( $d->ID ) );
		}
	}
	return $args;
}, 10, 2 );

/* Stránky autorů nejsou potřeba a prozrazují přihlašovací jméno – přesměrovat na úvod. */
add_action( 'template_redirect', function () {
	if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );

/* Seznam uživatelů přes REST API jen pro přihlášené (jinak by šlo zjistit přihlašovací jméno). */
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );

/* Ověřovací soubory Google Search Console (např. /google73ccff80234c18c5.html). */
function skalicka_google_verify_files() {
	return array( 'google73ccff80234c18c5.html' );
}
add_action( 'parse_request', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( in_array( $path, skalicka_google_verify_files(), true ) ) {
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo 'google-site-verification: ' . esc_html( $path );
		exit;
	}
}, 0 );
