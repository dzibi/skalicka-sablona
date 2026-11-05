<?php
/**
 * Měření webu:
 * 1) Vlastní statistika bez cookies (návštěvy stránek, kliky, odkud lidé přišli) – běží vždy, bez osobních údajů.
 * 2) Google Analytics 4, Meta Pixel, Microsoft Clarity – načtou se až po souhlasu v cookie liště.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- 1) Vlastní statistika bez cookies ---------- */

function skalicka_stats_is_bot() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) $_SERVER['HTTP_USER_AGENT'] ) : '';
	return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|preview|curl|wget|python|headless|lighthouse|monitor|facebookexternalhit|whatsapp/', $ua );
}

function skalicka_stats_key( $ts = null ) {
	return 'skalicka_stats_' . wp_date( 'Y_m', $ts );
}

function skalicka_stats_add( $bucket, $key, $n = 1 ) {
	$opt  = skalicka_stats_key();
	$data = get_option( $opt, array() );
	if ( ! is_array( $data ) ) {
		$data = array();
	}
	if ( ! isset( $data[ $bucket ] ) ) {
		$data[ $bucket ] = array();
	}
	$key                     = substr( (string) $key, 0, 120 );
	$data[ $bucket ][ $key ] = ( isset( $data[ $bucket ][ $key ] ) ? $data[ $bucket ][ $key ] : 0 ) + $n;
	update_option( $opt, $data, false );
}

/** Více přírůstků najednou (jedno čtení a zápis). $items = [[bucket, key, n], …] */
function skalicka_stats_add_many( array $items ) {
	$opt  = skalicka_stats_key();
	$data = get_option( $opt, array() );
	if ( ! is_array( $data ) ) {
		$data = array();
	}
	foreach ( $items as $it ) {
		list( $bucket, $key, $n ) = array_pad( $it, 3, 1 );
		$key = substr( (string) $key, 0, 160 );
		if ( ! isset( $data[ $bucket ] ) ) {
			$data[ $bucket ] = array();
		}
		$data[ $bucket ][ $key ] = ( isset( $data[ $bucket ][ $key ] ) ? $data[ $bucket ][ $key ] : 0 ) + $n;
	}
	update_option( $opt, $data, false );
}

function skalicka_stats_path( $p ) {
	$path = '/' . trim( (string) wp_parse_url( (string) $p, PHP_URL_PATH ), '/' );
	return '/' === $path ? '/' : $path . '/';
}

/* Zobrazení stránky počítá skript přes sendBeacon (spolehlivě jen skutečné prohlížeče, bez cookies). */
add_action( 'rest_api_init', function () {
	register_rest_route( 'skalicka/v1', '/s', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			if ( skalicka_stats_is_bot() || current_user_can( 'edit_posts' ) ) {
				return new WP_REST_Response( null, 204 );
			}
			$body = json_decode( (string) $req->get_body(), true );
			if ( ! is_array( $body ) ) {
				return new WP_REST_Response( null, 204 );
			}
			$type = isset( $body['t'] ) ? $body['t'] : '';
			$day  = wp_date( 'Y-m-d' );
			$add  = array();
			if ( 'pv' === $type ) {
				$path  = skalicka_stats_path( isset( $body['p'] ) ? $body['p'] : '/' );
				$add[] = array( 'pages', $path );
				$add[] = array( 'days', $day );
				$add[] = array( 'dp', $day . '|' . $path );
				$add[] = array( 'dh', $day . '|' . wp_date( 'G' ) );
				$ref   = isset( $body['r'] ) ? (string) wp_parse_url( (string) $body['r'], PHP_URL_HOST ) : '';
				$own   = (string) wp_parse_url( home_url(), PHP_URL_HOST );
				$src   = '';
				if ( $ref && $ref !== $own ) {
					$src = preg_replace( '/^(www\.|m\.|l\.|lm\.)/', '', $ref );
				} elseif ( ! $ref ) {
					$src = '(přímo / aplikace)';
				}
				if ( $src ) {
					$add[] = array( 'refs', $src );
					$add[] = array( 'dr', $day . '|' . $src );
				}
				$add[] = array( 'devices', isset( $body['m'] ) && $body['m'] ? 'mobil' : 'počítač' );
				$u     = isset( $body['u'] ) ? sanitize_text_field( (string) $body['u'] ) : '';
				if ( $u ) {
					$add[] = array( 'utm', $u );
					$add[] = array( 'du', $day . '|' . $u );
				}
			} elseif ( 'click' === $type && ! empty( $body['k'] ) ) {
				$k     = sanitize_key( $body['k'] );
				$add[] = array( 'clicks', $k );
				$add[] = array( 'dk', $day . '|' . $k );
			} elseif ( 'tm' === $type && ! empty( $body['p'] ) ) {
				$path  = skalicka_stats_path( $body['p'] );
				$sec   = max( 1, min( 1800, (int) ( $body['s'] ?? 0 ) ) );
				$dep   = max( 0, min( 100, (int) ( $body['d'] ?? 0 ) ) );
				$add[] = array( 'tsum', $path, $sec );
				$add[] = array( 'tcnt', $path );
				if ( $dep >= 50 ) {
					$add[] = array( 'scr50', $path );
				}
				if ( $dep >= 90 ) {
					$add[] = array( 'scr90', $path );
				}
			} elseif ( 'ck' === $type && ! empty( $body['l'] ) ) {
				$path  = skalicka_stats_path( isset( $body['p'] ) ? $body['p'] : '/' );
				$add[] = array( 'clk', $path . ' › ' . sanitize_text_field( mb_substr( (string) $body['l'], 0, 50 ) ) );
			}
			if ( $add ) {
				skalicka_stats_add_many( $add );
			}
			return new WP_REST_Response( null, 204 );
		},
	) );
} );

/* Přehled v administraci: Nástěnka → Návštěvnost */
add_action( 'admin_menu', function () {
	add_dashboard_page( 'Návštěvnost webu', 'Návštěvnost', 'manage_options', 'skalicka-stats', 'skalicka_stats_page' );
} );

function skalicka_click_labels() {
	return array(
		'rezervace_pohybovky' => 'Rezervace Pohybovek (Reenio)',
		'rezervace_oslavy'    => 'Rezervace oslavy (Reenio)',
		'rezervace_jine'      => 'Reenio – jiné',
		'clenstvi_nakup'      => 'Stát se členem (Stripe)',
		'clenstvi_portal'     => 'Spravovat členství (Stripe)',
		'navigace'            => 'Navigovat',
		'telefon'             => 'Zavolat',
		'email'               => 'E-mail',
		'instagram'           => 'Instagram',
		'facebook'            => 'Facebook',
		'kudyznudy'           => 'Kudy z nudy',
	);
}

function skalicka_stats_table( $title, $rows, $labels = array(), $limit = 15 ) {
	arsort( $rows );
	$rows  = array_slice( $rows, 0, $limit, true );
	$total = array_sum( $rows );
	echo '<h2>' . esc_html( $title ) . '</h2>';
	if ( ! $rows ) {
		echo '<p>Zatím žádná data.</p>';
		return;
	}
	echo '<table class="widefat striped" style="max-width:720px"><tbody>';
	foreach ( $rows as $k => $v ) {
		$pct = $total ? round( $v / $total * 100 ) : 0;
		echo '<tr><td>' . esc_html( isset( $labels[ $k ] ) ? $labels[ $k ] : $k ) . '</td><td style="width:90px;text-align:right"><strong>' . (int) $v . '</strong></td><td style="width:200px"><div style="background:#FECC06;height:10px;border-radius:5px;width:' . (int) $pct . '%"></div></td></tr>';
	}
	echo '</tbody></table>';
}

function skalicka_stats_page() {
	$month = isset( $_GET['m'] ) ? sanitize_text_field( wp_unslash( $_GET['m'] ) ) : wp_date( 'Y_m' );
	if ( ! preg_match( '/^\d{4}_\d{2}$/', $month ) ) {
		$month = wp_date( 'Y_m' );
	}
	$data = get_option( 'skalicka_stats_' . $month, array() );
	$days = isset( $data['days'] ) ? $data['days'] : array();
	ksort( $days );
	$prev = gmdate( 'Y_m', strtotime( str_replace( '_', '-', $month ) . '-01 -1 month' ) );
	$next = gmdate( 'Y_m', strtotime( str_replace( '_', '-', $month ) . '-01 +1 month' ) );
	?>
	<div class="wrap">
		<h1>Návštěvnost webu – <?php echo esc_html( str_replace( '_', ' / ', $month ) ); ?></h1>
		<p><a href="<?php echo esc_url( admin_url( 'index.php?page=skalicka-stats&m=' . $prev ) ); ?>">← předchozí měsíc</a> · <a href="<?php echo esc_url( admin_url( 'index.php?page=skalicka-stats&m=' . $next ) ); ?>">další měsíc →</a></p>
		<p>Počítá se bez cookies a bez osobních údajů, takže jde o všechny návštěvy (kromě robotů a vás přihlášených). Podrobnosti o lidech (věk, zájmy, cesta webem) najdete v Google Analytics a Clarity – ty ale vidí jen ty, kdo souhlasili s cookies.</p>
		<p style="font-size:18px">Zobrazení stránek celkem: <strong><?php echo (int) array_sum( $days ); ?></strong></p>
		<?php
		$pg    = isset( $data['pages'] ) ? $data['pages'] : array();
		$cl    = isset( $data['clicks'] ) ? $data['clicks'] : array();
		$f_v   = (int) ( $pg['/clenstvi/'] ?? 0 );
		$f_c   = (int) ( $cl['clenstvi_nakup'] ?? 0 );
		$f_d   = (int) ( $pg['/clenstvi/dekujeme/'] ?? 0 );
		$f_n   = max( 0, $f_c - $f_d );
		$boxes = array(
			array( 'Otevřeli stránku Členství', $f_v, '#A3CBD6' ),
			array( 'Klikli na „Stát se členem“', $f_c, '#FECC06' ),
			array( 'Dokončili nákup', $f_d, '#9BD58A' ),
			array( 'Nedokončili (klikli, nezaplatili)', $f_n, '#FBE1DC' ),
		);
		?>
		<h2>Trychtýř členství</h2>
		<div style="display:flex;flex-wrap:wrap;gap:10px;max-width:720px">
			<?php foreach ( $boxes as $b ) : ?>
				<div style="flex:1 1 150px;background:<?php echo esc_attr( $b[2] ); ?>;border-radius:12px;padding:12px 14px"><div style="font-size:26px;font-weight:700"><?php echo (int) $b[1]; ?></div><div><?php echo esc_html( $b[0] ); ?></div></div>
			<?php endforeach; ?>
		</div>
		<p style="max-width:720px;color:#646970">Dokončený nákup = návštěva děkovací stránky, kam Stripe přesměruje po zaplacení. Přesná čísla plateb jsou ve Stripu.</p>
		<?php if ( $days ) : ?>
			<h2>Po dnech</h2>
			<div style="display:flex;align-items:flex-end;gap:3px;height:140px;max-width:720px;border-bottom:1px solid #ccc">
				<?php
				$max = max( $days );
				foreach ( $days as $d => $v ) {
					echo '<div title="' . esc_attr( $d . ': ' . $v ) . '" style="flex:1;background:#A3CBD6;height:' . (int) round( $v / max( 1, $max ) * 100 ) . '%"></div>';
				}
				?>
			</div>
		<?php endif; ?>
		<?php
		skalicka_stats_table( 'Nejnavštěvovanější stránky', isset( $data['pages'] ) ? $data['pages'] : array() );
		skalicka_stats_table( 'Kliky na důležitá tlačítka', isset( $data['clicks'] ) ? $data['clicks'] : array(), skalicka_click_labels() );
		skalicka_stats_table( 'Odkud lidé přišli', isset( $data['refs'] ) ? $data['refs'] : array() );
		skalicka_stats_table( 'Zařízení', isset( $data['devices'] ) ? $data['devices'] : array() );
		do_action( 'skalicka_stats_extra', $data, $month );
		?>
	</div>
	<?php
}

/* ---------- 2) Nástroje třetích stran po souhlasu ---------- */

function skalicka_tracking_config() {
	return array(
		'ga'      => preg_match( '/^G-[A-Z0-9]+$/', (string) skalicka_opt( 'ga4_id' ) ) ? skalicka_opt( 'ga4_id' ) : '',
		'pixel'   => preg_match( '/^\d{6,20}$/', (string) skalicka_opt( 'pixel_id' ) ) ? skalicka_opt( 'pixel_id' ) : '',
		'clarity' => preg_match( '/^[a-z0-9]{6,20}$/', (string) skalicka_opt( 'clarity_id' ) ) ? skalicka_opt( 'clarity_id' ) : '',
		'stats'   => esc_url_raw( rest_url( 'skalicka/v1/s' ) ),
		'policy'  => skalicka_page_url( 'cookies' ),
	);
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'skalicka-souhlas', get_template_directory_uri() . '/assets/js/souhlas.js', array(), SKALICKA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'skalicka-souhlas', 'window.SKALICKA_TRACK = ' . wp_json_encode( skalicka_tracking_config() ) . ';', 'before' );
} );

/* Google Search Console – ověření vlastnictví (meta značka, bez cookies). */
add_action( 'wp_head', function () {
	$v = trim( (string) skalicka_opt( 'gsc_verify' ) );
	if ( $v ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $v ) . '">' . "\n";
	}
}, 2 );

/* Děkovací stránka po nákupu členství se nemá objevit ve vyhledávačích. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page() && 'dekujeme' === get_post_field( 'post_name', get_queried_object_id() ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );
