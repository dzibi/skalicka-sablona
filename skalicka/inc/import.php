<?php
/**
 * Import obrázků ze starého webu svetskalicka.cz do knihovny médií.
 * Jen pro správce, jen z povolených adres. Volá se z administrace (REST, s nonce).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_import_allowed_hosts() {
	// Starý web, fotky z profilu na Firmy.cz (Seznam) a z profilu na Google Mapách.
	return array(
		'svetskalicka.cz', 'www.svetskalicka.cz',
		'd48-a.sdn.cz',
		'lh3.googleusercontent.com', 'lh4.googleusercontent.com', 'lh5.googleusercontent.com', 'lh6.googleusercontent.com',
		wp_parse_url( home_url(), PHP_URL_HOST ),
	);
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'skalicka/v1', '/import-obrazku', array(
		'methods'             => 'POST',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'args'                => array(
			'url'   => array( 'required' => true, 'type' => 'string' ),
			'title' => array( 'required' => false, 'type' => 'string' ),
			'alt'   => array( 'required' => false, 'type' => 'string' ),
		),
		'callback'            => function ( WP_REST_Request $req ) {
			$url  = esc_url_raw( $req['url'] );
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( ! in_array( $host, skalicka_import_allowed_hosts(), true ) ) {
				return new WP_Error( 'skalicka_host', 'Adresa není povolená.', array( 'status' => 400 ) );
			}
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			// Stejný soubor neimportovat dvakrát.
			$existing = get_posts( array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_skalicka_source',
				'meta_value'  => $url,
				'fields'      => 'ids',
				'numberposts' => 1,
			) );
			if ( $existing ) {
				$id = (int) $existing[0];
			} else {
				$fetch = $url;
				if ( ! preg_match( '/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $fetch ) ) {
					$fetch .= ( false === strpos( $fetch, '?' ) ? '?' : '&' ) . 'skalicka=.jpg';
				}
				$id = media_sideload_image( $fetch, 0, $req['title'] ? sanitize_text_field( $req['title'] ) : null, 'id' );
				if ( is_wp_error( $id ) ) {
					return $id;
				}
				update_post_meta( $id, '_skalicka_source', $url );
			}
			if ( $req['alt'] ) {
				update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $req['alt'] ) );
			}
			return array(
				'id'    => $id,
				'url'   => wp_get_attachment_url( $id ),
				'large' => wp_get_attachment_image_url( $id, 'large' ),
			);
		},
	) );
} );
