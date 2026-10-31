<?php
/**
 * Počítadlo naběhaných kilometrů: [skalicka_kroky]
 *
 * Odhad z počtu vstupů (Dotykačka + členové). Stav zadává Steven jednou za měsíc
 * v Nastavení → Skalička, mezi aktualizacemi web přičítá průměr za den.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cesta: název, kumulativní km, „z …“, „do …“. */
function skalicka_kroky_trasa() {
	return array(
		array( 'Příbram', 0, 'z Příbrami', 'do Příbrami' ),
		array( 'Praha', 53, 'z Prahy', 'do Prahy' ),
		array( 'Vídeň', 306, 'z Vídně', 'do Vídně' ),
		array( 'Řím', 1070, 'z Říma', 'do Říma' ),
		array( 'Paříž', 2175, 'z Paříže', 'do Paříže' ),
		array( 'Londýn', 2519, 'z Londýna', 'do Londýna' ),
		array( 'Rovník', 8271, 'od rovníku', 'na rovník' ),
		array( 'Austrálie', 22837, 'ze Sydney', 'do Austrálie' ),
		array( 'Tokio', 30663, 'z Tokia', 'do Tokia' ),
		array( 'Kolem světa', 39786, 'z Příbrami', 'zpátky do Příbrami' ),
		array( 'Měsíc', 424186, 'z Měsíce', 'na Měsíc' ),
		array( 'Mars', 54600000, 'z Marsu', 'na Mars' ),
	);
}

function skalicka_kroky_config() {
	return array(
		'base'   => (float) skalicka_opt( 'kroky_vstupy' ),
		'date'   => (string) skalicka_opt( 'kroky_datum' ),
		'daily'  => (float) skalicka_opt( 'kroky_denne' ),
		'km'     => (float) skalicka_opt( 'kroky_km' ),
		'tod'    => (float) skalicka_opt( 'kroky_batolata' ) / 100,
		'kmTod'  => 0.3,
		'stride' => 0.45,
		'route'  => skalicka_kroky_trasa(),
	);
}

/** Vstupy k teď (stejný výpočet jako v JS, jen po celých dnech). */
function skalicka_kroky_entries( $c ) {
	$tz   = wp_timezone();
	$base = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $c['date'] . ' 23:59', $tz );
	if ( ! $base ) {
		return $c['base'];
	}
	$days = (int) floor( ( time() - $base->getTimestamp() ) / DAY_IN_SECONDS );
	return $c['base'] + max( 0, $days ) * $c['daily'];
}

function skalicka_kroky_nf( $n, $dec = 0 ) {
	return number_format( $n, $dec, ',', "\u{00A0}" );
}

function skalicka_kroky_svg_run() {
	return '<svg viewBox="0 0 140 140" aria-hidden="true" focusable="false">'
		. '<g stroke-linecap="round" stroke-linejoin="round" fill="none">'
		. '<path d="M10 70h22M16 84h18M8 98h20" stroke="#A3CBD6" stroke-width="5"/>'
		. '<path d="M68 92l-15 15-15-4" stroke="#E2AE86" stroke-width="10"/>'
		. '<path d="M75 61l-15 9-6-12" stroke="#E2AE86" stroke-width="9"/>'
		. '<path d="M81 55l-10 30" stroke="#FECC06" stroke-width="24"/>'
		. '<path d="M71 85l-2 6" stroke="#5B8FA0" stroke-width="22"/>'
		. '<path d="M73 93l18 12-5 19" stroke="#F6CBA4" stroke-width="10"/>'
		. '<path d="M81 61l15 12 11-10" stroke="#F6CBA4" stroke-width="9"/>'
		. '</g>'
		. '<ellipse cx="85" cy="127" rx="9" ry="5" fill="#3C3231"/>'
		. '<ellipse cx="35" cy="104" rx="5" ry="8" transform="rotate(60 35 104)" fill="#3C3231"/>'
		. '<circle cx="86" cy="33" r="17" fill="#F6CBA4"/>'
		. '<path d="M70 32c0-12 9-19 19-18 8 1 14 6 14 12-7-1-14-4-18-8-2 6-8 10-15 14z" fill="#3C3231"/>'
		. '<path d="M71 26c-7-2-12 2-13 8 5 0 9-2 12-5z" fill="#3C3231"/>'
		. '<circle cx="93" cy="34" r="2.3" fill="#3C3231"/>'
		. '<circle cx="95" cy="41" r="3.4" fill="#E8B3A6"/>'
		. '<path d="M88 43q4 3 8 0" stroke="#3C3231" stroke-width="2" fill="none" stroke-linecap="round"/>'
		. '</svg>';
}

function skalicka_kroky_svg_crawl() {
	return '<svg viewBox="0 0 140 140" aria-hidden="true" focusable="false">'
		. '<path d="M14 112h112" stroke="#E9DCCF" stroke-width="4" stroke-linecap="round"/>'
		. '<g stroke-linecap="round" stroke-linejoin="round" fill="none">'
		. '<path d="M50 86l10 22H36" stroke="#E2AE86" stroke-width="10"/>'
		. '<path d="M84 86l6 22" stroke="#E2AE86" stroke-width="9"/>'
		. '<path d="M46 80h38" stroke="#D3B2A8" stroke-width="30"/>'
		. '<path d="M56 90l8 18H40" stroke="#F6CBA4" stroke-width="10"/>'
		. '<path d="M90 88l10 20" stroke="#F6CBA4" stroke-width="9"/>'
		. '</g>'
		. '<circle cx="106" cy="68" r="19" fill="#F6CBA4"/>'
		. '<path d="M98 50c2-6 9-7 12-3-4 0-6 2-6 5z" fill="#3C3231"/>'
		. '<circle cx="114" cy="66" r="2.3" fill="#3C3231"/>'
		. '<circle cx="114" cy="74" r="3.4" fill="#E8B3A6"/>'
		. '<path d="M108 77q4 3 8 0" stroke="#3C3231" stroke-width="2" fill="none" stroke-linecap="round"/>'
		. '<circle cx="92" cy="70" r="4" fill="#E2AE86"/>'
		. '</svg>';
}

add_shortcode( 'skalicka_kroky', function () {
	wp_enqueue_script( 'skalicka-zemekoule', get_template_directory_uri() . '/assets/js/zemekoule.js', array(), SKALICKA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	$c     = skalicka_kroky_config();
	$e     = skalicka_kroky_entries( $c );
	$run   = $e * ( 1 - $c['tod'] ) * $c['km'];
	$crawl = $e * $c['tod'] * $c['kmTod'];
	$total = $run + $crawl;
	$route = $c['route'];

	// Aktuální úsek cesty.
	$i = 1;
	while ( $i < count( $route ) - 1 && $total >= $route[ $i ][1] ) {
		$i++;
	}
	$from = $route[ $i - 1 ];
	$to   = $route[ $i ];
	$pct  = max( 0, min( 100, ( $total - $from[1] ) / max( 1, $to[1] - $from[1] ) * 100 ) );

	ob_start();
	?>
	<section class="sk-kroky" data-kroky="<?php echo esc_attr( wp_json_encode( $c ) ); ?>">
		<div class="sk-kroky-head">
			<h2>Děti běží kolem světa</h2>
			<p class="sk-kroky-od">Od otevření v září 2024</p>
			<p>Kolik kilometrů už u nás děti naběhaly a nalezly. Počítadlo běží, když máme otevřeno.</p>
		</div>
		<p class="sk-kroky-total"><span class="sk-kroky-num" data-k="total"><?php echo esc_html( skalicka_kroky_nf( floor( $total ) ) ); ?></span><span class="sk-kroky-dec" data-k="dec">,<?php echo esc_html( sprintf( '%03d', ( $total - floor( $total ) ) * 1000 ) ); ?></span> <span class="sk-kroky-unit">km</span></p>
		<p class="sk-kroky-steps">To je asi <strong data-k="steps"><?php echo esc_html( skalicka_kroky_nf( $total * 1000 / $c['stride'] ) ); ?></strong> dětských kroků.</p>

		<div class="sk-kroky-kids">
			<div class="sk-kid sk-kid-run">
				<?php echo skalicka_kroky_svg_run(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<strong data-k="run"><?php echo esc_html( skalicka_kroky_nf( $run ) ); ?> km</strong>
				<span>naběhaly děti</span>
			</div>
			<div class="sk-kid sk-kid-crawl">
				<?php echo skalicka_kroky_svg_crawl(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<strong data-k="crawl"><?php echo esc_html( skalicka_kroky_nf( $crawl ) ); ?> km</strong>
				<span>nalezla batolata</span>
			</div>
		</div>

		<div class="sk-trip">
			<div class="sk-globe" aria-hidden="true"></div>
			<p class="sk-trip-now">Právě běžíme <b data-k="from"><?php echo esc_html( $from[2] ); ?></b> <b data-k="to"><?php echo esc_html( $to[3] ); ?></b>. Zbývá <b data-k="left"><?php echo esc_html( skalicka_kroky_nf( $to[1] - $total ) ); ?> km</b>.</p>
			<div class="sk-trip-bar" role="presentation"><span data-k="bar" style="width:<?php echo esc_attr( round( $pct, 1 ) ); ?>%"></span></div>
			<ol class="sk-trip-list">
				<?php foreach ( array_slice( $route, 1 ) as $n => $r ) : ?>
					<?php $cls = $total >= $r[1] ? 'is-done' : ( $n + 1 === $i ? 'is-next' : '' ); ?>
					<li class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $r[0] ); ?> <small><?php echo esc_html( skalicka_kroky_nf( $r[1] ) ); ?> km</small></li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );
