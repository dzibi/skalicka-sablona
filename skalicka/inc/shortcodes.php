<?php
/**
 * Krátké kódy pro obsah stránek:
 * [skalicka_dnes] [skalicka_pohybovky pocet="3"] [skalicka_hodiny] [skalicka_tlacitko typ="…" text="…" styl="zluta|svetla"]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Velký semafor se třemi světly. Text doplní skript podle aktuálního času. */
add_shortcode( 'skalicka_semafor', function () {
	return '<div class="sk-sem" data-stav="">'
		. '<div class="sk-sem-box" aria-hidden="true"><span class="sk-sem-l sk-sem-r"></span><span class="sk-sem-l sk-sem-o"></span><span class="sk-sem-l sk-sem-g"></span></div>'
		. '<div class="sk-sem-txt"><div role="status" aria-live="polite"><strong class="sk-sem-title">' . esc_html( skalicka_hours_short() ) . '</strong><span class="sk-sem-sub"></span></div>'
		. '<span class="sk-sem-clockrow" hidden><span class="sk-sem-clock" aria-hidden="true">--:--:--</span><span class="sk-sem-clocklbl"></span></span></div>'
		. '</div>';
} );

/* Pruh „Dnes u nás“. */
add_shortcode( 'skalicka_dnes', function () {
	$tz     = wp_timezone();
	$now    = new DateTimeImmutable( 'now', $tz );
	$start  = $now->setTime( 0, 0 );
	$end    = $start->modify( '+1 day' );
	$items  = array();
	$manual = skalicka_manual_closure();
	$hours  = skalicka_hours();
	$today  = $hours[ (int) $now->format( 'N' ) ];

	if ( $manual ) {
		$items[] = '<span class="sk-pill sk-pill--zavreno">Dnes zavřeno' . ( $manual['msg'] ? ': ' . esc_html( $manual['msg'] ) : '' ) . '</span>';
	}

	foreach ( skalicka_events_between( $start, $end ) as $e ) {
		if ( $e['end'] <= $now ) {
			continue;
		}
		if ( in_array( SKALICKA_CAT_CLOSED, $e['cats'], true ) ) {
			$label   = $e['allday'] ? 'Dnes zavřeno' : skalicka_time( $e['start'] ) . '–' . skalicka_time( $e['end'] ) . ' zavřeno';
			$items[] = '<span class="sk-pill sk-pill--zavreno">' . esc_html( $label . ': ' . $e['title'] ) . '</span>';
		} elseif ( in_array( SKALICKA_CAT_POHYBOVKY, $e['cats'], true ) ) {
			$items[] = '<span class="sk-pill">' . esc_html( skalicka_time( $e['start'] ) . ' ' . $e['title'] ) . ' · <a href="' . esc_url( skalicka_link( 'pohybovky' ) ) . '">Rezervovat</a></span>';
		} else {
			$time    = $e['allday'] ? '' : skalicka_time( $e['start'] ) . ' ';
			$items[] = '<span class="sk-pill"><a href="' . esc_url( get_permalink( $e['id'] ) ) . '">' . esc_html( $time . $e['title'] ) . '</a></span>';
		}
	}

	if ( ! $items ) {
		$items[] = $today
			? '<span class="sk-pill">Dnes otevřeno ' . esc_html( skalicka_format_range( $today ) ) . ', přijďte bez rezervace.</span>'
			: '<span class="sk-pill sk-pill--zavreno">Dnes zavřeno</span>';
	}

	return '<div class="sk-dnes"><div class="sk-dnes-in"><span class="sk-dnes-title">Dnes u nás</span>' . implode( '', $items ) . '</div></div>';
} );

/* Nejbližší Pohybovky. */
add_shortcode( 'skalicka_pohybovky', function ( $atts ) {
	$atts   = shortcode_atts( array( 'pocet' => 3 ), $atts );
	$events = skalicka_upcoming_events( SKALICKA_CAT_POHYBOVKY, max( 1, (int) $atts['pocet'] ) );
	if ( ! $events ) {
		return '<p class="sk-empty">Další termíny brzy doplníme. <a href="' . esc_url( skalicka_calendar_url() ) . '">Kalendář akcí</a></p>';
	}
	$html = '<div class="sk-events">';
	foreach ( $events as $e ) {
		$html .= '<article class="sk-event">';
		$html .= '<div class="sk-date"><span>' . esc_html( skalicka_weekday_short( $e['start'] ) ) . '</span><b>' . esc_html( $e['start']->format( 'j' ) ) . '</b><span>' . esc_html( skalicka_month_gen( $e['start']->format( 'n' ) ) ) . '</span></div>';
		$html .= '<div><h3>' . esc_html( $e['title'] ) . '</h3>';
		$html .= '<p>' . esc_html( skalicka_time( $e['start'] ) ) . ' · 3–6 let · platíte na místě, členové zdarma</p>';
		$html .= '<div class="sk-event-links"><a href="' . esc_url( skalicka_link( 'pohybovky' ) ) . '">Rezervovat</a><a href="' . esc_url( get_permalink( $e['id'] ) ) . '">Detail a do kalendáře</a></div>';
		$html .= '</div></article>';
	}
	return $html . '</div>';
} );

/* Otevírací doba po dnech. */
add_shortcode( 'skalicka_hodiny', function () {
	$hours = skalicka_hours();
	$names = array( 1 => 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle' );
	if ( count( array_unique( $hours ) ) === 1 ) {
		return '<p class="sk-price">' . esc_html( skalicka_hours_short() ) . '</p>';
	}
	$html = '<ul class="sk-hours">';
	foreach ( $names as $n => $name ) {
		$html .= '<li><span>' . esc_html( $name ) . '</span><strong>' . esc_html( $hours[ $n ] ? skalicka_format_range( $hours[ $n ] ) : 'zavřeno' ) . '</strong></li>';
	}
	return $html . '</ul>';
} );

/* Tlačítko s odkazem z Nastavení → Skalička (Stripe, Reenio, navigace, telefon…). */
add_shortcode( 'skalicka_tlacitko', function ( $atts ) {
	$atts  = shortcode_atts( array( 'typ' => '', 'text' => '', 'styl' => '' ), $atts );
	$url   = skalicka_link( $atts['typ'] );
	$class = 'sk-btn' . ( $atts['styl'] ? ' sk-btn--' . sanitize_html_class( $atts['styl'] ) : '' );
	$text  = $atts['text'] ? $atts['text'] : 'Více';
	$ext   = $url && 0 === strpos( $url, 'http' ) && false === strpos( $url, home_url() );
	if ( ! $url ) {
		$note = current_user_can( 'manage_options' ) ? '<span class="sk-admin-note">Chybí odkaz – doplňte v Nastavení → Skalička (vidí jen správce).</span>' : '';
		return '<span><a class="' . esc_attr( $class ) . ' sk-btn-missing" href="#">' . esc_html( $text ) . '</a>' . $note . '</span>';
	}
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"' . ( $ext ? ' rel="noopener"' : '' ) . '>' . esc_html( $text ) . '</a>';
} );

/* Shortcode bloky nejsou v odstavci – necháme je vypsat jako bloky. */
add_filter( 'no_texturize_shortcodes', function ( $list ) {
	return array_merge( $list, array( 'skalicka_semafor', 'skalicka_dnes', 'skalicka_pohybovky', 'skalicka_hodiny', 'skalicka_tlacitko' ) );
} );
