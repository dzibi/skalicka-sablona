<?php
/**
 * Nastavení herny (otevírací doba, kontakty, odkazy) a stránka Semafor pro obsluhu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_defaults() {
	return array(
		'hours_1'           => '09:00-18:30',
		'hours_2'           => '09:00-18:30',
		'hours_3'           => '09:00-18:30',
		'hours_4'           => '09:00-18:30',
		'hours_5'           => '09:00-18:30',
		'hours_6'           => '09:00-18:30',
		'hours_7'           => '09:00-18:30',
		'soon'              => 30,
		'phone'             => '',
		'email'             => 'info@svetskalicka.cz',
		'address'           => "OC Skalka, 3. patro\nMilínská 134, 261 01 Příbram",
		'company'           => 'Kapilsons s.r.o., IČ 21371741',
		'map_url'           => 'https://www.google.com/maps/search/?api=1&query=D%C4%9Btsk%C3%BD%20sv%C4%9Bt%20Skali%C4%8Dka%2C%20Mil%C3%ADnsk%C3%A1%20134%2C%20P%C5%99%C3%ADbram',
		'ig_url'            => '',
		'fb_url'            => '',
		'url_member'        => '',
		'url_portal'        => '',
		'url_res_pohybovky' => '',
		'url_res_oslavy'    => '',
		'ga4_id'            => '',
		'pixel_id'          => '',
		'clarity_id'        => '',
		'gsc_verify'        => '',
		'report_email'      => 'info@svetskalicka.cz',
		'kroky_vstupy'      => '12970',
		'kroky_datum'       => '2026-10-30',
		'kroky_denne'       => '14.5',
		'kroky_km'          => '2',
		'kroky_batolata'    => '15',
	);
}

function skalicka_opt( $key ) {
	$opts = wp_parse_args( (array) get_option( 'skalicka_nastaveni', array() ), skalicka_defaults() );
	return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
}

/** Otevírací doba po dnech: 1 = pondělí … 7 = neděle, hodnota „09:00-18:30“ nebo prázdná (zavřeno). */
function skalicka_hours() {
	$out = array();
	for ( $d = 1; $d <= 7; $d++ ) {
		$v         = trim( (string) skalicka_opt( 'hours_' . $d ) );
		$out[ $d ] = preg_match( '/^\d{1,2}:\d{2}-\d{1,2}:\d{2}$/', $v ) ? $v : '';
	}
	return $out;
}

/** Externí odkazy podle typu (vyplňují se v Nastavení → Skalička). */
function skalicka_link( $type ) {
	switch ( $type ) {
		case 'clenstvi':
			return skalicka_opt( 'url_member' );
		case 'portal':
			return skalicka_opt( 'url_portal' );
		case 'pohybovky':
			$u = skalicka_opt( 'url_res_pohybovky' );
			return $u ? $u : skalicka_page_url( 'lekce' );
		case 'oslavy':
			$u = skalicka_opt( 'url_res_oslavy' );
			return $u ? $u : skalicka_page_url( 'oslavy' );
		case 'navigace':
			return skalicka_opt( 'map_url' );
		case 'telefon':
			$p = preg_replace( '/[^0-9+]/', '', (string) skalicka_opt( 'phone' ) );
			return $p ? 'tel:' . $p : '';
		case 'email':
			$e = skalicka_opt( 'email' );
			return $e ? 'mailto:' . $e : '';
		case 'instagram':
			return skalicka_opt( 'ig_url' );
		case 'facebook':
			return skalicka_opt( 'fb_url' );
		case 'kalendar':
			return skalicka_calendar_url();
	}
	return '';
}

/* ---------- Role Obsluha ---------- */

function skalicka_staff_caps() {
	$caps = array( 'read' => true, 'skalicka_semafor' => true, 'edit_posts' => true );
	foreach ( array( 'tribe_events', 'tribe_venues', 'tribe_organizers' ) as $t ) {
		foreach ( array( 'edit_', 'edit_others_', 'edit_published_', 'publish_', 'delete_', 'delete_others_', 'delete_published_', 'read_private_', 'edit_private_', 'delete_private_' ) as $p ) {
			$caps[ $p . $t ] = true;
		}
	}
	return $caps;
}

function skalicka_setup_roles() {
	remove_role( 'skalicka_obsluha' );
	add_role( 'skalicka_obsluha', 'Obsluha herny', skalicka_staff_caps() );
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'skalicka_semafor' );
	}
	update_option( 'skalicka_roles_version', SKALICKA_VERSION );
}
add_action( 'after_switch_theme', 'skalicka_setup_roles' );
add_action( 'admin_init', function () {
	if ( get_option( 'skalicka_roles_version' ) !== SKALICKA_VERSION ) {
		skalicka_setup_roles();
	}
} );

/* ---------- Administrace ---------- */

add_action( 'admin_menu', function () {
	add_options_page( 'Nastavení herny', 'Skalička', 'manage_options', 'skalicka', 'skalicka_settings_page' );
	add_menu_page( 'Semafor', 'Semafor', 'skalicka_semafor', 'skalicka-semafor', 'skalicka_semafor_page', 'dashicons-clock', 3 );
} );

add_action( 'admin_init', function () {
	register_setting( 'skalicka', 'skalicka_nastaveni', array(
		'type'              => 'array',
		'sanitize_callback' => 'skalicka_sanitize',
		'default'           => skalicka_defaults(),
	) );
} );

function skalicka_sanitize( $in ) {
	$d   = skalicka_defaults();
	$out = array();
	foreach ( $d as $k => $v ) {
		$val = isset( $in[ $k ] ) ? $in[ $k ] : '';
		if ( 0 === strpos( $k, 'hours_' ) ) {
			$val = preg_replace( '/\s+/', '', str_replace( array( '–', '—' ), '-', (string) $val ) );
			$out[ $k ] = preg_match( '/^\d{1,2}:\d{2}-\d{1,2}:\d{2}$/', $val ) ? $val : '';
		} elseif ( 'soon' === $k ) {
			$out[ $k ] = max( 0, min( 120, (int) $val ) );
		} elseif ( 'kroky_datum' === $k ) {
			$out[ $k ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', trim( (string) $val ) ) ? trim( (string) $val ) : $d[ $k ];
		} elseif ( 0 === strpos( $k, 'kroky_' ) ) {
			$num       = (float) str_replace( array( ' ', ',' ), array( '', '.' ), (string) $val );
			$out[ $k ] = $num >= 0 ? (string) $num : $d[ $k ];
		} elseif ( 'address' === $k ) {
			$out[ $k ] = sanitize_textarea_field( $val );
		} elseif ( 'email' === $k || 'report_email' === $k ) {
			$out[ $k ] = sanitize_email( $val );
		} elseif ( false !== strpos( $k, 'url' ) ) {
			$out[ $k ] = esc_url_raw( trim( (string) $val ) );
		} else {
			$out[ $k ] = sanitize_text_field( $val );
		}
	}
	return $out;
}

function skalicka_settings_page() {
	$o      = wp_parse_args( (array) get_option( 'skalicka_nastaveni', array() ), skalicka_defaults() );
	$names  = array( 1 => 'Pondělí', 2 => 'Úterý', 3 => 'Středa', 4 => 'Čtvrtek', 5 => 'Pátek', 6 => 'Sobota', 7 => 'Neděle' );
	$fields = array(
		'phone'             => array( 'Telefon herny', 'Např. +420 123 456 789' ),
		'email'             => array( 'E-mail', '' ),
		'company'           => array( 'Firma v patičce', '' ),
		'map_url'           => array( 'Odkaz na navigaci (Google Maps / Mapy.cz)', '' ),
		'ig_url'            => array( 'Instagram', 'Celá adresa profilu' ),
		'fb_url'            => array( 'Facebook', 'Celá adresa stránky' ),
		'url_member'        => array( 'Stripe: nákup členství', 'Platební odkaz Stripe (buy.stripe.com/…)' ),
		'url_portal'        => array( 'Stripe: zákaznický portál', 'Odkaz pro přihlášení do portálu (billing.stripe.com/p/login/…)' ),
		'url_res_pohybovky' => array( 'Reenio: rezervace Pohybovek', '' ),
		'url_res_oslavy'    => array( 'Reenio: rezervace oslav', '' ),
		'ga4_id'            => array( 'Google Analytics 4: ID měření', 'Tvar G-XXXXXXXXXX. Načte se až po souhlasu s cookies.' ),
		'pixel_id'          => array( 'Meta Pixel: ID', 'Jen číslice. Načte se až po souhlasu s marketingovými cookies.' ),
		'clarity_id'        => array( 'Microsoft Clarity: ID projektu', 'Heatmapy a záznamy pohybu po webu. Načte se až po souhlasu.' ),
		'gsc_verify'        => array( 'Google Search Console: ověřovací kód', 'Jen hodnota content z meta značky google-site-verification.' ),
		'report_email'      => array( 'E-mail pro report návštěvnosti', 'Každé pondělí ráno za minulý týden, prvního v měsíci za celý měsíc.' ),
	);
	?>
	<div class="wrap">
		<h1>Nastavení herny</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'skalicka' ); ?>
			<h2>Otevírací doba</h2>
			<p>Formát <code>09:00-18:30</code>. Prázdné pole = zavřeno. Svátky a mimořádné zavření zadávejte do kalendáře akcí (kategorie „Zavřeno“).</p>
			<table class="form-table" role="presentation">
				<?php foreach ( $names as $n => $label ) : ?>
					<tr>
						<th scope="row"><label for="sk-h<?php echo (int) $n; ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input id="sk-h<?php echo (int) $n; ?>" type="text" class="regular-text" name="skalicka_nastaveni[hours_<?php echo (int) $n; ?>]" value="<?php echo esc_attr( $o[ 'hours_' . $n ] ); ?>"></td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="sk-soon">Oranžová před otevřením a zavřením</label></th>
					<td><input id="sk-soon" type="number" min="0" max="120" name="skalicka_nastaveni[soon]" value="<?php echo esc_attr( $o['soon'] ); ?>"> minut</td>
				</tr>
			</table>
			<h2>Kontakty a odkazy</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sk-address">Adresa</label></th>
					<td><textarea id="sk-address" class="large-text" rows="2" name="skalicka_nastaveni[address]"><?php echo esc_textarea( $o['address'] ); ?></textarea></td>
				</tr>
				<?php foreach ( $fields as $k => $f ) : ?>
					<tr>
						<th scope="row"><label for="sk-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
						<td>
							<input id="sk-<?php echo esc_attr( $k ); ?>" type="text" class="large-text" name="skalicka_nastaveni[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $o[ $k ] ); ?>">
							<?php if ( $f[1] ) : ?><p class="description"><?php echo esc_html( $f[1] ); ?></p><?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h2>Počítadlo kilometrů</h2>
			<p>Jednou za měsíc přepište první dvě pole. Vstupy = součet z Dotykačky (kategorie Vstup bez „Opakovaný vstup“) + návštěvy členů. Mezi aktualizacemi web přičítá odhad podle průměru za den, jen v otevírací době.</p>
			<table class="form-table" role="presentation">
				<?php
				$kroky = array(
					'kroky_vstupy'   => array( 'Vstupů celkem od otevření', 'Zaplacené vstupy dětí (bez miminek do 6 měsíců).' ),
					'kroky_datum'    => array( 'Stav ke dni', 'Formát 2026-11-30 (počítá se včetně tohoto dne).' ),
					'kroky_denne'    => array( 'Průměr vstupů za den', 'Pro živé přičítání mezi aktualizacemi.' ),
					'kroky_km'       => array( 'Km za jednu návštěvu', 'Odhad pro běhající dítě za 2 hodiny hraní.' ),
					'kroky_batolata' => array( 'Podíl lezoucích batolat v %', 'Batole na jednu návštěvu odhadem 0,3 km.' ),
				);
				foreach ( $kroky as $k => $f ) :
					?>
					<tr>
						<th scope="row"><label for="sk-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
						<td>
							<input id="sk-<?php echo esc_attr( $k ); ?>" type="text" class="regular-text" name="skalicka_nastaveni[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( $o[ $k ] ); ?>">
							<p class="description"><?php echo esc_html( $f[1] ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Uložit' ); ?>
		</form>
		<?php skalicka_update_box(); ?>
	</div>
	<?php
}

/* ---------- Semafor: ruční zavření na dnešek ---------- */

function skalicka_manual_closure() {
	$s = (array) get_option( 'skalicka_semafor', array() );
	if ( ! empty( $s['date'] ) && wp_date( 'Y-m-d' ) === $s['date'] ) {
		return array(
			'msg' => isset( $s['msg'] ) ? $s['msg'] : '',
		);
	}
	return null;
}

add_action( 'admin_post_skalicka_semafor', function () {
	if ( ! current_user_can( 'skalicka_semafor' ) ) {
		wp_die( 'Nemáte oprávnění.' );
	}
	check_admin_referer( 'skalicka_semafor' );
	if ( isset( $_POST['zavrit'] ) ) {
		$msg = isset( $_POST['msg'] ) ? sanitize_text_field( wp_unslash( $_POST['msg'] ) ) : '';
		update_option( 'skalicka_semafor', array( 'date' => wp_date( 'Y-m-d' ), 'msg' => $msg ) );
	} else {
		update_option( 'skalicka_semafor', array() );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=skalicka-semafor&ulozeno=1' ) );
	exit;
} );

function skalicka_semafor_page() {
	$manual = skalicka_manual_closure();
	?>
	<div class="wrap" style="max-width:640px">
		<h1>Semafor</h1>
		<?php if ( isset( $_GET['ulozeno'] ) ) : ?>
			<div class="notice notice-success"><p>Uloženo. Web se upraví během chvilky.</p></div>
		<?php endif; ?>
		<p style="font-size:16px">Teď na webu: <strong id="sk-admin-status">načítám…</strong></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="skalicka_semafor">
			<?php wp_nonce_field( 'skalicka_semafor' ); ?>
			<?php if ( $manual ) : ?>
				<p style="font-size:16px">Herna je dnes ručně <strong>zavřená</strong><?php echo $manual['msg'] ? ': ' . esc_html( $manual['msg'] ) : ''; ?>. Zítra se semafor vrátí k běžné otevírací době sám.</p>
				<p><button class="button button-primary button-hero" name="otevrit" value="1">Zrušit – herna je otevřená</button></p>
			<?php else : ?>
				<p><label for="sk-msg" style="font-size:16px">Důvod (zobrazí se na webu)</label><br>
				<input id="sk-msg" name="msg" type="text" class="large-text" placeholder="např. technická závada"></p>
				<p><button class="button button-primary button-hero" name="zavrit" value="1" onclick="return confirm('Opravdu dnes zavřít hernu na webu?')">Dnes zavřeno</button></p>
				<p>Platí jen pro dnešek. Plánované zavření (svátek, soukromá akce) zadejte do <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tribe_events' ) ); ?>">kalendáře</a> v kategorii „Zavřeno“.</p>
			<?php endif; ?>
		</form>
		<script>window.SKALICKA = <?php echo wp_json_encode( skalicka_status_config() ); ?>;</script>
		<script src="<?php echo esc_url( get_template_directory_uri() . '/assets/js/skalicka.js?ver=' . SKALICKA_VERSION ); ?>" defer></script>
	</div>
	<?php
}

/* ---------- Data pro semafor (vloženo do stránky + REST bez cache) ---------- */

function skalicka_status_config() {
	$tz       = wp_timezone();
	$today    = new DateTimeImmutable( 'today', $tz );
	$tomorrow = $today->modify( '+1 day' );
	$manual   = skalicka_manual_closure();
	return array(
		'hours'          => skalicka_hours(),
		'soon'           => (int) skalicka_opt( 'soon' ),
		'today'          => $today->format( 'Y-m-d' ),
		'closures'       => skalicka_day_closures( $today ),
		'tomorrowClosed' => skalicka_day_closed_all( $tomorrow ),
		'ahead'          => skalicka_closures_ahead( $today, 7 ),
		'manual'         => $manual ? true : false,
		'manualMsg'      => $manual ? $manual['msg'] : '',
		'rest'           => esc_url_raw( rest_url( 'skalicka/v1/stav' ) ),
	);
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'skalicka/v1', '/stav', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			$res = new WP_REST_Response( skalicka_status_config() );
			$res->header( 'Cache-Control', 'no-store, max-age=0' );
			return $res;
		},
	) );
} );
