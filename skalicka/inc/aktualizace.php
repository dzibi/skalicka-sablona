<?php
/**
 * Aktualizace šablony z GitHubu (veřejný repozitář s kódem šablony, bez fotek a osobních údajů).
 * Nová verze = GitHub release s přiloženým souborem skalicka-sablona.zip.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SKALICKA_REPO = 'dzibi/skalicka-sablona';

function skalicka_update_latest( $force = false ) {
	$cached = get_transient( 'skalicka_update_latest' );
	if ( $cached && ! $force ) {
		return $cached;
	}
	$res = wp_remote_get( 'https://api.github.com/repos/' . SKALICKA_REPO . '/releases/latest', array(
		'timeout' => 10,
		'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'skalicka-sablona' ),
	) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return null;
	}
	$j   = json_decode( wp_remote_retrieve_body( $res ), true );
	$zip = '';
	foreach ( (array) ( $j['assets'] ?? array() ) as $a ) {
		if ( 'skalicka-sablona.zip' === ( $a['name'] ?? '' ) ) {
			$zip = $a['browser_download_url'];
		}
	}
	$out = array(
		'version' => ltrim( (string) ( $j['tag_name'] ?? '' ), 'v' ),
		'notes'   => (string) ( $j['body'] ?? '' ),
		'zip'     => $zip,
	);
	set_transient( 'skalicka_update_latest', $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

function skalicka_update_run() {
	$latest = skalicka_update_latest( true );
	if ( ! $latest || ! $latest['zip'] ) {
		return new WP_Error( 'skalicka_no_release', 'Na GitHubu není žádná vydaná verze se souborem skalicka-sablona.zip.' );
	}
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/theme.php';
	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );
	$result   = $upgrader->install( $latest['zip'], array( 'overwrite_package' => true ) );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	if ( ! $result ) {
		$errors = $skin->get_errors();
		return is_wp_error( $errors ) && $errors->has_errors() ? $errors : new WP_Error( 'skalicka_update_failed', 'Aktualizace se nepovedla.' );
	}
	wp_clean_themes_cache();
	return $latest['version'];
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'skalicka/v1', '/aktualizace', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => function () { return current_user_can( 'update_themes' ); },
			'callback'            => function () {
				$l = skalicka_update_latest( true );
				return array( 'installed' => SKALICKA_VERSION, 'latest' => $l ? $l['version'] : null, 'zip' => $l ? $l['zip'] : null );
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => function () { return current_user_can( 'update_themes' ); },
			'callback'            => function () {
				$r = skalicka_update_run();
				if ( is_wp_error( $r ) ) {
					return new WP_REST_Response( array( 'ok' => false, 'error' => $r->get_error_message() ), 500 );
				}
				return array( 'ok' => true, 'version' => $r );
			},
		),
	) );
} );

/* Tlačítko v Nastavení → Skalička */
add_action( 'admin_post_skalicka_update', function () {
	if ( ! current_user_can( 'update_themes' ) ) {
		wp_die( 'Nemáte oprávnění.' );
	}
	check_admin_referer( 'skalicka_update' );
	$r = skalicka_update_run();
	$q = is_wp_error( $r ) ? array( 'sk_upd' => 'err', 'sk_msg' => rawurlencode( $r->get_error_message() ) ) : array( 'sk_upd' => 'ok', 'sk_msg' => rawurlencode( $r ) );
	wp_safe_redirect( add_query_arg( $q, admin_url( 'options-general.php?page=skalicka' ) ) );
	exit;
} );

function skalicka_update_box() {
	$l = skalicka_update_latest();
	?>
	<h2>Aktualizace šablony</h2>
	<?php if ( isset( $_GET['sk_upd'] ) ) : ?>
		<div class="notice <?php echo 'ok' === $_GET['sk_upd'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo 'ok' === $_GET['sk_upd'] ? 'Šablona aktualizována na verzi ' : 'Chyba: '; ?><?php echo esc_html( rawurldecode( wp_unslash( $_GET['sk_msg'] ?? '' ) ) ); ?></p></div>
	<?php endif; ?>
	<p>Nainstalovaná verze: <strong><?php echo esc_html( SKALICKA_VERSION ); ?></strong>. Nejnovější na GitHubu: <strong><?php echo esc_html( $l && $l['version'] ? $l['version'] : 'zatím žádná' ); ?></strong>.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="skalicka_update">
		<?php wp_nonce_field( 'skalicka_update' ); ?>
		<?php submit_button( 'Aktualizovat šablonu z GitHubu', 'secondary', 'submit', false, ( $l && $l['zip'] && version_compare( $l['version'], SKALICKA_VERSION, '>' ) ) ? array() : array( 'disabled' => 'disabled' ) ); ?>
	</form>
	<?php
}
