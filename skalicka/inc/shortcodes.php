<?php
/**
 * Krátké kódy pro obsah stránek:
 * [skalicka_dnes] [skalicka_tyden] [skalicka_clenstvi_obr] [skalicka_pohybovky pocet="3"] [skalicka_hodiny] [skalicka_tlacitko typ="…" text="…" styl="zluta|svetla"]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Živý stav akce: data-sk-od / data-sk-do pro skript (přepne na „teď probíhá“ a po konci akci skryje)
 * a třída is-ted, když akce už běží. Celodenní akce se nepřepínají.
 */
function skalicka_live( $e, $now ) {
	if ( $e['allday'] ) {
		return array( '', '' );
	}
	$attrs = ' data-sk-od="' . (int) $e['start']->getTimestamp() . '" data-sk-do="' . (int) $e['end']->getTimestamp() . '"';
	return array( $attrs, $e['start'] <= $now ? ' is-ted' : '' );
}

/* Dnešní akce do semaforu (zavírání ukazuje semafor sám). */
function skalicka_sem_dnes() {
	$tz    = wp_timezone();
	$now   = new DateTimeImmutable( 'now', $tz );
	$start = $now->setTime( 0, 0 );
	$out   = array();
	foreach ( skalicka_events_between( $start, $start->modify( '+1 day' ) ) as $e ) {
		if ( $e['end'] <= $now || in_array( SKALICKA_CAT_CLOSED, $e['cats'], true ) ) {
			continue;
		}
		$poh   = in_array( SKALICKA_CAT_POHYBOVKY, $e['cats'], true );
		$url   = $poh ? skalicka_link( 'pohybovky' ) : get_permalink( $e['id'] );
		$time  = $e['allday'] ? 'celý den ' : skalicka_time( $e['start'] ) . ' ';
		$live  = skalicka_live( $e, $now );
		$out[] = '<a class="sk-sem-akce' . ( $poh ? ' is-pohybovky is-rez' : '' ) . $live[1] . '" href="' . esc_url( $url ) . '"' . $live[0] . '>'
			. '<span class="sk-sem-tag"><span class="sk-jen-pred">Dnes</span><span class="sk-jen-ted">Teď</span></span>'
			. '<span class="sk-sem-akce-txt"><span class="sk-jen-pred">' . esc_html( $time ) . '</span>' . esc_html( $e['title'] )
			. ( $e['allday'] ? '' : '<span class="sk-jen-ted"> · probíhá do&nbsp;' . esc_html( skalicka_time( $e['end'] ) ) . '</span>' )
			. '</span></a>';
	}
	return $out ? '<div class="sk-sem-dnes">' . implode( '', $out ) . '</div>' : '';
}

/* Velký semafor se třemi světly. Text doplní skript podle aktuálního času. */
add_shortcode( 'skalicka_semafor', function () {
	return '<div class="sk-sem" data-stav="">'
		. '<div class="sk-sem-box" aria-hidden="true"><span class="sk-sem-l sk-sem-r"></span><span class="sk-sem-l sk-sem-o"></span><span class="sk-sem-l sk-sem-g"></span></div>'
		. '<div class="sk-sem-txt"><div role="status" aria-live="polite"><strong class="sk-sem-title">' . esc_html( skalicka_hours_short() ) . '</strong><span class="sk-sem-sub"></span></div>'
		. '<span class="sk-sem-clockrow" hidden><span class="sk-sem-clock" aria-hidden="true">--:--:--</span><span class="sk-sem-clocklbl"></span></span>'
		. skalicka_sem_dnes() . '</div>'
		. '</div>';
} );

/**
 * Položky pro jeden den (dnes / zítra): zavírání, Pohybovky, ostatní akce.
 * U dneška se vynechá, co už skončilo. Bez akcí vrátí otevírací dobu.
 *
 * @return string[] HTML štítky
 */
function skalicka_day_pills( DateTimeImmutable $day, $is_today ) {
	$tz     = wp_timezone();
	$now    = new DateTimeImmutable( 'now', $tz );
	$start  = $day->setTime( 0, 0 );
	$end    = $start->modify( '+1 day' );
	$items  = array();
	$hours  = skalicka_hours();
	$range  = $hours[ (int) $start->format( 'N' ) ];
	$manual = $is_today ? skalicka_manual_closure() : null;
	$closed = false;

	if ( $manual ) {
		$closed  = true;
		$items[] = '<span class="sk-pill sk-pill--zavreno">Zavřeno' . ( $manual['msg'] ? ': ' . esc_html( $manual['msg'] ) : '' ) . '</span>';
	}
	foreach ( skalicka_events_between( $start, $end ) as $e ) {
		if ( $is_today && $e['end'] <= $now ) {
			continue;
		}
		$time = $e['allday'] ? '' : skalicka_time( $e['start'] ) . ' ';
		if ( in_array( SKALICKA_CAT_CLOSED, $e['cats'], true ) ) {
			$closed  = $closed || $e['allday'];
			$label   = $e['allday'] ? 'Zavřeno' : skalicka_time( $e['start'] ) . '–' . skalicka_time( $e['end'] ) . ' zavřeno';
			$items[] = '<span class="sk-pill sk-pill--zavreno">' . esc_html( $label . ': ' . $e['title'] ) . '</span>';
		} elseif ( in_array( SKALICKA_CAT_POHYBOVKY, $e['cats'], true ) ) {
			$live    = skalicka_live( $e, $now );
			$items[] = '<span class="sk-pill sk-pill--pohybovky' . $live[1] . '"' . $live[0] . '>'
				. '<span class="sk-jen-pred">' . esc_html( $time . $e['title'] ) . ' · <a href="' . esc_url( skalicka_link( 'pohybovky' ) ) . '">Rezervovat</a></span>'
				. '<span class="sk-jen-ted"><b>Teď probíhá:</b> ' . esc_html( $e['title'] ) . ', do&nbsp;' . esc_html( skalicka_time( $e['end'] ) ) . '</span></span>';
		} else {
			$live    = skalicka_live( $e, $now );
			$items[] = '<span class="sk-pill sk-pill--akce' . $live[1] . '"' . $live[0] . '><a href="' . esc_url( get_permalink( $e['id'] ) ) . '">'
				. '<span class="sk-jen-pred">' . esc_html( $time . $e['title'] ) . '</span>'
				. '<span class="sk-jen-ted"><b>Teď probíhá:</b> ' . esc_html( $e['title'] ) . ', do&nbsp;' . esc_html( skalicka_time( $e['end'] ) ) . '</span></a></span>';
		}
	}
	if ( ! $closed ) {
		array_unshift( $items, $range
			? '<span class="sk-pill sk-pill--hodiny">Otevřeno ' . esc_html( skalicka_format_range( $range ) ) . '</span>'
			: '<span class="sk-pill sk-pill--zavreno">Zavřeno</span>' );
	}
	return $items;
}

/* Pruh „Dnes a zítra“ pod úvodní částí. */
add_shortcode( 'skalicka_dnes', function () {
	$tz       = wp_timezone();
	$today    = new DateTimeImmutable( 'today', $tz );
	$tomorrow = $today->modify( '+1 day' );
	$html     = '<div class="sk-dnes"><div class="sk-dnes-in sk-dnes-2">';
	foreach ( array( array( $today, 'Dnes', true ), array( $tomorrow, 'Zítra', false ) ) as $d ) {
		$html .= '<div class="sk-dnes-den"><span class="sk-dnes-title">' . esc_html( $d[1] ) . ' <small>' . esc_html( mb_strtolower( skalicka_weekday_short( $d[0] ) ) . ' ' . $d[0]->format( 'j. n.' ) ) . '</small></span>'
			. '<div class="sk-dnes-pills">' . implode( '', skalicka_day_pills( $d[0], $d[2] ) ) . '</div></div>';
	}
	return $html . '</div></div>';
} );

/**
 * [skalicka_tyden] – akce na tento a příští týden (po–ne), ve dvou sloupcích.
 */
add_shortcode( 'skalicka_tyden', function () {
	$tz      = wp_timezone();
	$now     = new DateTimeImmutable( 'now', $tz );
	$monday  = $now->setTime( 0, 0 )->modify( '-' . ( (int) $now->format( 'N' ) - 1 ) . ' days' );
	$weeks   = array(
		array( 'Tento týden', $now, $monday->modify( '+7 days' ), 'sk-tyden--ted' ),
		array( 'Příští týden', $monday->modify( '+7 days' ), $monday->modify( '+14 days' ), 'sk-tyden--pristi' ),
	);
	$html = '<div class="sk-tydny">';
	foreach ( $weeks as $w ) {
		$list = array_values( array_filter( skalicka_events_between( $w[1], $w[2] ), function ( $e ) use ( $now ) {
			return $e['end'] > $now;
		} ) );
		$range = $w[0] === 'Tento týden'
			? $monday->format( 'j. n.' ) . ' – ' . $monday->modify( '+6 days' )->format( 'j. n.' )
			: $w[1]->format( 'j. n.' ) . ' – ' . $w[1]->modify( '+6 days' )->format( 'j. n.' );
		$html .= '<section class="sk-tyden ' . esc_attr( $w[3] ) . '"><h3>' . esc_html( $w[0] ) . ' <small>' . esc_html( $range ) . '</small></h3>';
		if ( ! $list ) {
			$html .= '<p class="sk-tyden-prazdno">Žádná zvláštní akce, herna běží normálně. Přijďte bez rezervace.</p>';
		} else {
			$html .= '<ul class="sk-tyden-list">';
			foreach ( $list as $e ) {
				$is_closed = in_array( SKALICKA_CAT_CLOSED, $e['cats'], true );
				$is_poh    = in_array( SKALICKA_CAT_POHYBOVKY, $e['cats'], true );
				$cls       = $is_closed ? 'is-zavreno' : ( $is_poh ? 'is-pohybovky' : 'is-akce' );
				$is_today  = $e['start']->format( 'Y-m-d' ) === $now->format( 'Y-m-d' );
				$time      = $e['allday'] ? 'celý den' : skalicka_time( $e['start'] ) . '–' . skalicka_time( $e['end'] );
				$live      = skalicka_live( $e, $now );
				$html     .= '<li class="' . $cls . ( $is_today ? ' is-dnes' : '' ) . $live[1] . '"' . $live[0] . '>'
					. '<span class="sk-tyden-den"><b>' . esc_html( skalicka_weekday_short( $e['start'] ) ) . '</b>' . esc_html( $e['start']->format( 'j. n.' ) ) . '</span>'
					. '<span class="sk-tyden-co"><strong>' . esc_html( ( $is_closed ? 'Zavřeno: ' : '' ) . $e['title'] ) . '</strong>'
					. '<span class="sk-jen-pred">' . esc_html( $time ) . ( $is_today ? ' · dnes' : '' ) . '</span>'
					. ( $e['allday'] ? '' : '<span class="sk-jen-ted"><b>Teď probíhá</b>, do&nbsp;' . esc_html( skalicka_time( $e['end'] ) ) . '</span>' ) . '</span>'
					. ( $is_poh ? '<a class="sk-tyden-btn sk-jen-pred" href="' . esc_url( skalicka_link( 'pohybovky' ) ) . '">Rezervovat</a><span class="sk-tyden-btn sk-tyden-btn--off sk-jen-ted" aria-hidden="true">Probíhá</span>' : ( $is_closed ? '' : '<a class="sk-tyden-btn" href="' . esc_url( get_permalink( $e['id'] ) ) . '">Detail</a>' ) )
					. '</li>';
			}
			$html .= '</ul>';
		}
		$html .= '</section>';
	}
	return $html . '</div>';
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
		$live  = skalicka_live( $e, new DateTimeImmutable( 'now', wp_timezone() ) );
		$html .= '<article class="sk-event' . $live[1] . '"' . $live[0] . '>';
		$html .= '<div class="sk-date"><span>' . esc_html( skalicka_weekday_short( $e['start'] ) ) . '</span><b>' . esc_html( $e['start']->format( 'j' ) ) . '</b><span>' . esc_html( skalicka_month_gen( $e['start']->format( 'n' ) ) ) . '</span></div>';
		$html .= '<div><h3>' . esc_html( $e['title'] ) . '</h3>';
		$html .= '<p>' . esc_html( skalicka_time( $e['start'] ) ) . ' · 3–6 let · platíte na místě, členové zdarma</p>';
		$html .= '<div class="sk-event-links"><a class="sk-jen-pred" href="' . esc_url( skalicka_link( 'pohybovky' ) ) . '">Rezervovat</a><span class="sk-jen-ted sk-event-ted">Teď probíhá, do&nbsp;' . esc_html( skalicka_time( $e['end'] ) ) . '</span><a href="' . esc_url( get_permalink( $e['id'] ) ) . '">Detail a do kalendáře</a></div>';
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
	return array_merge( $list, array( 'skalicka_semafor', 'skalicka_dnes', 'skalicka_tyden', 'skalicka_hero', 'skalicka_obr', 'skalicka_clenstvi_obr', 'skalicka_pohybovky', 'skalicka_hodiny', 'skalicka_tlacitko' ) );
} );

/* [skalicka_clenstvi_obr] – ilustrace členství v mobilu (QR kód, Aktivní, výhody). */
add_shortcode( 'skalicka_clenstvi_obr', function () {
	$svg = (string) @file_get_contents( get_template_directory() . '/assets/img/clenstvi.svg' ); // phpcs:ignore
	if ( ! $svg ) {
		return '';
	}
	$id   = (int) get_option( 'site_icon' );
	$logo = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
	$svg  = str_replace( '__LOGO__', esc_url( $logo ), $svg );
	return '<figure class="sk-clen-obr">' . $svg . '</figure>';
} );

/* [skalicka_obr nazev="vstup|lekce|clenstvi"] – animované ilustrace na kartách (soubory v assets/img). */
add_shortcode( 'skalicka_obr', function ( $atts ) {
	$atts = shortcode_atts( array( 'nazev' => '' ), $atts );
	$name = preg_replace( '/[^a-z]/', '', (string) $atts['nazev'] );
	$file = get_template_directory() . '/assets/img/' . $name . '.svg';
	if ( ! $name || ! is_readable( $file ) ) {
		return '';
	}
	$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false !== strpos( $svg, '__LOGO__' ) ) {
		$id  = (int) get_option( 'site_icon' );
		$svg = str_replace( '__LOGO__', esc_url( $id ? (string) wp_get_attachment_image_url( $id, 'thumbnail' ) : '' ), $svg );
	}
	return '<figure class="sk-clen-obr sk-obr sk-obr--' . esc_attr( $name ) . '">' . $svg . '</figure>';
} );

/* [skalicka_hero ids="501,53"] – hlavní fotka v úvodu, fotky se pomalu prolínají. */
add_shortcode( 'skalicka_hero', function ( $atts ) {
	$atts = shortcode_atts( array( 'ids' => '' ), $atts );
	$ids  = array_values( array_filter( array_map( 'absint', explode( ',', (string) $atts['ids'] ) ) ) );
	if ( ! $ids ) {
		return '';
	}
	$html = '';
	foreach ( $ids as $i => $id ) {
		$html .= wp_get_attachment_image( $id, 'large', false, array(
			'class'         => 'sk-slide sk-slide-' . ( $i + 1 ),
			'loading'       => 0 === $i ? 'eager' : 'lazy',
			'fetchpriority' => 0 === $i ? 'high' : 'low',
			'sizes'         => '(max-width: 700px) 90vw, 560px',
		) );
	}
	return '<figure class="wp-block-image size-large sk-hero-show sk-hero-show--' . count( $ids ) . '"><div class="sk-slides">' . $html . '</div></figure>';
} );
