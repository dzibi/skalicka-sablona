<?php
/**
 * Šablona Skalička – základní nastavení.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKALICKA_VERSION', '1.10.10' );

require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/events.php';
require_once __DIR__ . '/inc/shortcodes.php';
require_once __DIR__ . '/inc/import.php';
require_once __DIR__ . '/inc/kroky.php';
require_once __DIR__ . '/inc/analytika.php';
require_once __DIR__ . '/inc/pasy.php';
require_once __DIR__ . '/inc/ilustrace.php';
require_once __DIR__ . '/inc/aktualizace.php';
require_once __DIR__ . '/inc/loader.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 160,
		'width'       => 160,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );

	register_nav_menus( array(
		'hlavni' => 'Hlavní menu',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'skalicka', get_stylesheet_uri(), array(), SKALICKA_VERSION );
	wp_enqueue_script( 'skalicka', get_template_directory_uri() . '/assets/js/skalicka.js', array(), SKALICKA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_enqueue_script( 'skalicka-fotky', get_template_directory_uri() . '/assets/js/fotky.js', array(), SKALICKA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'skalicka', 'window.SKALICKA = ' . wp_json_encode( skalicka_status_config() ) . ';', 'before' );
} );

/* Předběžné načtení hlavního písma pro rychlejší první zobrazení. */
add_action( 'wp_head', function () {
	$base = get_template_directory_uri() . '/assets/fonts/';
	echo '<link rel="preload" href="' . esc_url( $base . 'nunito-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $base . 'just-another-hand-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<meta name="theme-color" content="#A3CBD6">' . "\n";
}, 1 );

/* Styly tlačítek v editoru: žlutá a světlá. */
add_action( 'init', function () {
	if ( function_exists( 'register_block_style' ) ) {
		register_block_style( 'core/button', array( 'name' => 'sk-zluta', 'label' => 'Žlutá' ) );
		register_block_style( 'core/button', array( 'name' => 'sk-svetla', 'label' => 'Světlá' ) );
	}
} );

/**
 * Odkaz na stránku podle její adresy (slug). Pokud stránka neexistuje, vrátí adresu na webu.
 */
function skalicka_page_url( $path ) {
	$page = get_page_by_path( $path );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/' . trim( $path, '/' ) . '/' );
}

/** Odkaz na kalendář akcí (The Events Calendar, jinak /kalendar/). */
function skalicka_calendar_url() {
	if ( function_exists( 'tribe_get_events_link' ) ) {
		return tribe_get_events_link();
	}
	return home_url( '/kalendar/' );
}

/** Položky hlavního menu, když v administraci není menu vytvořené. */
function skalicka_default_menu_items() {
	return array(
		array( 'Herna', skalicka_page_url( 'herna-a-vstupne' ) ),
		array( 'Členství', skalicka_page_url( 'clenstvi' ) ),
		array( 'Lekce', skalicka_page_url( 'lekce' ) ),
		array( 'Oslavy', skalicka_page_url( 'oslavy' ) ),
		array( 'Kalendář', skalicka_calendar_url() ),
		array( 'Návštěva', skalicka_page_url( 'navsteva' ) ),
	);
}

function skalicka_render_menu() {
	if ( has_nav_menu( 'hlavni' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'hlavni',
			'container'      => false,
			'depth'          => 1,
		) );
		return;
	}
	$current = trailingslashit( home_url( add_query_arg( array() ) ) );
	echo '<ul>';
	foreach ( skalicka_default_menu_items() as $item ) {
		$is_current = ( trailingslashit( $item[1] ) === $current );
		printf(
			'<li><a href="%s"%s>%s</a></li>',
			esc_url( $item[1] ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $item[0] )
		);
	}
	echo '</ul>';
}

/** Rozbalovací nabídka Rezervovat (hlavička a mobilní lišta). */
function skalicka_reservation_menu() {
	$poh    = skalicka_link( 'pohybovky' );
	$oslavy = skalicka_link( 'oslavy' );
	?>
	<div class="sk-rez-menu">
		<a href="<?php echo esc_url( $poh ); ?>">Pohybovky<small>lekce pro 3–6 let, platíte na místě</small></a>
		<a href="<?php echo esc_url( $oslavy ); ?>">Oslava<small>pro 8 až 16 dětí</small></a>
	</div>
	<?php
}

/** Otevírací doba v krátkém tvaru, např. „Každý den 9:00–18:30“. */
function skalicka_hours_short() {
	$hours  = skalicka_hours();
	$unique = array_unique( $hours );
	if ( count( $unique ) === 1 ) {
		$h = reset( $unique );
		return $h ? 'Každý den ' . skalicka_format_range( $h ) : 'Zavřeno';
	}
	$out = array();
	foreach ( skalicka_day_names() as $n => $name ) {
		$out[] = $name . ' ' . ( $hours[ $n ] ? skalicka_format_range( $hours[ $n ] ) : 'zavřeno' );
	}
	return implode( ', ', $out );
}

function skalicka_day_names() {
	return array( 1 => 'Po', 2 => 'Út', 3 => 'St', 4 => 'Čt', 5 => 'Pá', 6 => 'So', 7 => 'Ne' );
}

/** „09:00-18:30“ → „9:00–18:30“. */
function skalicka_format_range( $range ) {
	$parts = explode( '-', $range );
	if ( count( $parts ) !== 2 ) {
		return $range;
	}
	return skalicka_format_time( $parts[0] ) . '–' . skalicka_format_time( $parts[1] );
}

function skalicka_format_time( $hm ) {
	$hm = trim( $hm );
	return preg_replace( '/^0(\d)/', '$1', $hm );
}

/** Vlna pod modrou hlavičkou stránek. */
function skalicka_wave() {
	echo '<svg class="sk-wave" aria-hidden="true" viewBox="0 0 1440 60" preserveAspectRatio="none"><path d="M0 30 C240 70 480 0 720 30 C960 60 1200 0 1440 28 L1440 60 L0 60 Z" fill="#ffffff"/></svg>';
}

/* Bez emoji skriptu z wordpress.org (rychlost a GDPR). */
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
} );

/* Stará adresa /pohybovky/ vede na novou stránku Lekce. */
add_action( 'template_redirect', function () {
	if ( is_404() ) {
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' );
		if ( 'pohybovky' === $path ) {
			wp_safe_redirect( skalicka_page_url( 'lekce' ), 301 );
			exit;
		}
	}
}, 1 );
