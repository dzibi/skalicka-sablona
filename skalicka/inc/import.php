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
		// Export z Canvy (fotky jídel bez pozadí pro menu).
		'export-download.canva.com', 'document-export.canva.com',
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
			'orez'  => array( 'required' => false, 'type' => 'boolean' ),
			'nazev' => array( 'required' => false, 'type' => 'string' ),
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
			} elseif ( $req['orez'] ) {
				// PNG bez pozadí: oříznout průhledné okraje a vycentrovat do čtverce (jednotné fotky v menu).
				$tmp = download_url( $url, 60 );
				if ( is_wp_error( $tmp ) ) {
					return $tmp;
				}
				if ( ! skalicka_import_trim_square( $tmp ) ) {
					wp_delete_file( $tmp );
					return new WP_Error( 'skalicka_trim', 'Obrázek se nepodařilo upravit (čeká se PNG s průhledným pozadím).', array( 'status' => 400 ) );
				}
				$name = sanitize_file_name( ( $req['nazev'] ? $req['nazev'] : 'menu-foto' ) . '.png' );
				add_filter( 'image_editor_output_format', 'skalicka_import_webp_sizes' );
				$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, $req['title'] ? sanitize_text_field( $req['title'] ) : null );
				remove_filter( 'image_editor_output_format', 'skalicka_import_webp_sizes' );
				if ( is_wp_error( $id ) ) {
					wp_delete_file( $tmp );
					return $id;
				}
				update_post_meta( $id, '_skalicka_source', $url );
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

/** Menší velikosti fotek bez pozadí ukládat jako WebP (zachová průhlednost, je lehčí). */
function skalicka_import_webp_sizes( $formats ) {
	if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		$formats['image/png'] = 'image/webp';
	}
	return $formats;
}

/**
 * Ořízne průhledné okraje PNG, vycentruje motiv do průhledného čtverce s malým okrajem
 * a uloží zpět jako PNG o straně $size px. Vrací true/false.
 */
function skalicka_import_trim_square( $path, $size = 900, $pad = 0.05 ) {
	if ( ! function_exists( 'imagecreatefrompng' ) ) {
		return false;
	}
	$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $info || IMAGETYPE_PNG !== $info[2] ) {
		return false;
	}
	$src = @imagecreatefrompng( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $src ) {
		return false;
	}
	$w    = imagesx( $src );
	$h    = imagesy( $src );
	$minx = $w;
	$miny = $h;
	$maxx = -1;
	$maxy = -1;
	for ( $y = 0; $y < $h; $y += 2 ) {
		for ( $x = 0; $x < $w; $x += 2 ) {
			$a = ( imagecolorat( $src, $x, $y ) >> 24 ) & 0x7F;
			if ( $a < 100 ) {
				if ( $x < $minx ) {
					$minx = $x;
				}
				if ( $x > $maxx ) {
					$maxx = $x;
				}
				if ( $y < $miny ) {
					$miny = $y;
				}
				if ( $y > $maxy ) {
					$maxy = $y;
				}
			}
		}
	}
	if ( $maxx < 0 ) {
		imagedestroy( $src );
		return false;
	}
	$minx = max( 0, $minx - 2 );
	$miny = max( 0, $miny - 2 );
	$maxx = min( $w - 1, $maxx + 2 );
	$maxy = min( $h - 1, $maxy + 2 );
	$bw   = $maxx - $minx + 1;
	$bh   = $maxy - $miny + 1;
	$side = max( $bw, $bh ) * ( 1 + 2 * $pad );
	$k    = $size / $side;
	$dst  = imagecreatetruecolor( $size, $size );
	imagealphablending( $dst, false );
	imagesavealpha( $dst, true );
	imagefill( $dst, 0, 0, imagecolorallocatealpha( $dst, 255, 255, 255, 127 ) );
	$dw = (int) round( $bw * $k );
	$dh = (int) round( $bh * $k );
	imagecopyresampled( $dst, $src, (int) round( ( $size - $dw ) / 2 ), (int) round( ( $size - $dh ) / 2 ), $minx, $miny, $dw, $dh, $bw, $bh );
	$ok = imagepng( $dst, $path, 9 );
	imagedestroy( $src );
	imagedestroy( $dst );
	return (bool) $ok;
}
