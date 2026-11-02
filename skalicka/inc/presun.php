<?php
/**
 * Jednorázový přesun složky webu při přejmenování hostingu brdskasnidane.cz → svetskalicka.cz
 * (www/domains/brdskasnidane.cz → www/domains/svetskalicka.cz). Jen pro administrátora.
 * Po přepnutí domény se soubor ze šablony odstraní.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_presun_cesty() {
	$cur = rtrim( ABSPATH, '/' );
	return array( $cur, dirname( $cur ) . '/svetskalicka.cz' );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'skalicka/v1', '/presun', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
			'callback'            => function () {
				list( $from, $to ) = skalicka_presun_cesty();
				return array(
					'from'            => $from,
					'to'              => $to,
					'from_exists'     => is_dir( $from ),
					'to_exists'       => file_exists( $to ),
					'parent_writable' => is_writable( dirname( $from ) ),
				);
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
			'callback'            => function () {
				list( $from, $to ) = skalicka_presun_cesty();
				if ( 'brdskasnidane.cz' !== basename( $from ) || ! is_dir( $from ) || file_exists( $to ) ) {
					return new WP_REST_Response( array( 'ok' => false, 'error' => 'Stav složek nesedí, nic jsem neměnil.' ), 409 );
				}
				$ok  = @rename( $from, $to ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				$err = $ok ? null : error_get_last();
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( array( 'ok' => $ok, 'to' => $to, 'error' => $err ? $err['message'] : null ) );
				exit;
			},
		),
	) );
} );
