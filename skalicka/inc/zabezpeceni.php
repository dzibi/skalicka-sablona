<?php
/**
 * Zabezpečení webu: bezpečnostní hlavičky, vypnuté XML-RPC, zákaz úprav kódu z administrace,
 * obecná chybová hláška při přihlášení.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Bezpečnostní hlavičky pro všechny stránky. */
function skalicka_security_headers() {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}
add_action( 'send_headers', 'skalicka_security_headers' );
add_action( 'login_init', 'skalicka_security_headers' );
add_action( 'admin_init', 'skalicka_security_headers' );

/* XML-RPC se nepoužívá (mobilní aplikace WordPress jede přes REST a hesla aplikací) – vypnout úplně. */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', function () {
	return array();
} );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );
add_action( 'init', function () {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		status_header( 403 );
		exit( 'XML-RPC je vypnuté.' );
	}
}, 0 );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* Úpravy PHP kódu šablony a pluginů přímo v administraci zakázat (kdyby někdo získal přihlášení). */
add_filter( 'map_meta_cap', function ( $caps, $cap ) {
	if ( in_array( $cap, array( 'edit_themes', 'edit_plugins', 'edit_files' ), true ) ) {
		return array( 'do_not_allow' );
	}
	return $caps;
}, 10, 2 );

/* Při chybném přihlášení neprozrazovat, jestli existuje uživatelské jméno. */
add_filter( 'login_errors', function ( $error ) {
	global $errors;
	if ( is_wp_error( $errors ) ) {
		$codes = $errors->get_error_codes();
		if ( array_intersect( $codes, array( 'invalid_username', 'invalid_email', 'incorrect_password' ) ) ) {
			return '<strong>Chyba:</strong> Nesprávné přihlašovací údaje.';
		}
	}
	return $error;
} );
