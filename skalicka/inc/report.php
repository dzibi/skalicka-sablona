<?php
/**
 * Počasí, svátky a prázdniny ke statistice + pravidelný report e-mailem
 * (každé pondělí za minulý týden, prvního v měsíci za minulý měsíc s CSV).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Počasí v Příbrami (Open-Meteo, bez klíče) ---------- */

function skalicka_weather_update( $past_days = 7 ) {
	$url = add_query_arg( array(
		'latitude'      => '49.69',
		'longitude'     => '14.01',
		'daily'         => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code',
		'timezone'      => 'Europe/Prague',
		'past_days'     => (int) $past_days,
		'forecast_days' => 1,
	), 'https://api.open-meteo.com/v1/forecast' );
	$res = wp_remote_get( $url, array( 'timeout' => 15 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return false;
	}
	$j = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $j['daily']['time'] ) ) {
		return false;
	}
	$w     = (array) get_option( 'skalicka_pocasi', array() );
	$today = wp_date( 'Y-m-d' );
	foreach ( $j['daily']['time'] as $i => $d ) {
		if ( $d > $today ) {
			continue;
		}
		$w[ $d ] = array(
			'max'  => isset( $j['daily']['temperature_2m_max'][ $i ] ) ? round( (float) $j['daily']['temperature_2m_max'][ $i ] ) : null,
			'min'  => isset( $j['daily']['temperature_2m_min'][ $i ] ) ? round( (float) $j['daily']['temperature_2m_min'][ $i ] ) : null,
			'sraz' => isset( $j['daily']['precipitation_sum'][ $i ] ) ? round( (float) $j['daily']['precipitation_sum'][ $i ], 1 ) : null,
			'kod'  => isset( $j['daily']['weather_code'][ $i ] ) ? (int) $j['daily']['weather_code'][ $i ] : null,
		);
	}
	ksort( $w );
	update_option( 'skalicka_pocasi', $w, false );
	return true;
}

function skalicka_weather_word( $code ) {
	if ( null === $code ) {
		return '';
	}
	if ( 0 === $code ) {
		return 'jasno';
	}
	if ( $code <= 2 ) {
		return 'polojasno';
	}
	if ( 3 === $code ) {
		return 'zataženo';
	}
	if ( $code <= 48 ) {
		return 'mlha';
	}
	if ( $code <= 57 ) {
		return 'mrholení';
	}
	if ( $code <= 67 ) {
		return 'déšť';
	}
	if ( $code <= 77 ) {
		return 'sníh';
	}
	if ( $code <= 82 ) {
		return 'přeháňky';
	}
	if ( $code <= 86 ) {
		return 'sněhové přeháňky';
	}
	return 'bouřka';
}

function skalicka_weather_text( $ymd ) {
	$w = get_option( 'skalicka_pocasi', array() );
	if ( empty( $w[ $ymd ] ) ) {
		return '';
	}
	$d   = $w[ $ymd ];
	$txt = skalicka_weather_word( $d['kod'] );
	if ( null !== $d['max'] ) {
		$txt .= ( $txt ? ', ' : '' ) . $d['max'] . ' °C';
	}
	if ( $d['sraz'] && $d['sraz'] >= 0.5 ) {
		$txt .= ', ' . str_replace( '.', ',', (string) $d['sraz'] ) . ' mm';
	}
	return $txt;
}

/* ---------- Svátky a školní prázdniny ---------- */

function skalicka_easter( $y ) {
	$a = $y % 19; $b = intdiv( $y, 100 ); $c = $y % 100; $d = intdiv( $b, 4 ); $e = $b % 4;
	$f = intdiv( $b + 8, 25 ); $g = intdiv( $b - $f + 1, 3 ); $h = ( 19 * $a + $b - $d - $g + 15 ) % 30;
	$i = intdiv( $c, 4 ); $k = $c % 4; $l = ( 32 + 2 * $e + 2 * $i - $h - $k ) % 7; $m = intdiv( $a + 11 * $h + 22 * $l, 451 );
	$month = intdiv( $h + $l - 7 * $m + 114, 31 ); $day = ( ( $h + $l - 7 * $m + 114 ) % 31 ) + 1;
	return sprintf( '%04d-%02d-%02d', $y, $month, $day );
}

function skalicka_svatek( $ymd ) {
	$y     = (int) substr( $ymd, 0, 4 );
	$md    = substr( $ymd, 5 );
	$fixed = array(
		'01-01' => 'Nový rok', '05-01' => 'Svátek práce', '05-08' => 'Den vítězství', '07-05' => 'Cyril a Metoděj',
		'07-06' => 'Jan Hus', '09-28' => 'Den české státnosti', '10-28' => 'Vznik Československa', '11-17' => 'Den boje za svobodu',
		'12-24' => 'Štědrý den', '12-25' => '1. svátek vánoční', '12-26' => '2. svátek vánoční',
	);
	if ( isset( $fixed[ $md ] ) ) {
		return $fixed[ $md ];
	}
	$e = new DateTimeImmutable( skalicka_easter( $y ) );
	if ( $ymd === $e->modify( '-2 days' )->format( 'Y-m-d' ) ) {
		return 'Velký pátek';
	}
	if ( $ymd === $e->modify( '+1 day' )->format( 'Y-m-d' ) ) {
		return 'Velikonoční pondělí';
	}
	return '';
}

/** Školní prázdniny pro Příbram (MŠMT). Každý školní rok doplnit. */
function skalicka_prazdniny( $ymd ) {
	$list = array(
		array( '2026-10-29', '2026-10-30', 'podzimní prázdniny' ),
		array( '2026-12-23', '2027-01-03', 'vánoční prázdniny' ),
		array( '2027-01-29', '2027-01-29', 'pololetní prázdniny' ),
		array( '2027-02-08', '2027-02-14', 'jarní prázdniny (Příbram)' ),
		array( '2027-03-25', '2027-03-26', 'velikonoční prázdniny' ),
		array( '2027-07-01', '2027-08-31', 'letní prázdniny' ),
	);
	foreach ( $list as $p ) {
		if ( $ymd >= $p[0] && $ymd <= $p[1] ) {
			return $p[2];
		}
	}
	return '';
}

function skalicka_den_poznamka( $ymd ) {
	return trim( implode( ', ', array_filter( array( skalicka_svatek( $ymd ), skalicka_prazdniny( $ymd ) ) ) ) );
}

/* ---------- Data za období ---------- */

function skalicka_dny_cz() {
	return array( 1 => 'po', 2 => 'út', 3 => 'st', 4 => 'čt', 5 => 'pá', 6 => 'so', 7 => 'ne' );
}

/** Sečte denní data mezi $from a $to (včetně). */
function skalicka_report_data( DateTimeImmutable $from, DateTimeImmutable $to ) {
	$out = array(
		'days' => array(), 'pages' => array(), 'refs' => array(), 'utm' => array(), 'clicks' => array(),
		'hours' => array_fill( 0, 24, 0 ), 'wd' => array_fill( 1, 7, 0 ), 'total' => 0,
	);
	$cache = array();
	for ( $d = $from; $d <= $to; $d = $d->modify( '+1 day' ) ) {
		$ymd = $d->format( 'Y-m-d' );
		$key = 'skalicka_stats_' . $d->format( 'Y_m' );
		if ( ! isset( $cache[ $key ] ) ) {
			$cache[ $key ] = (array) get_option( $key, array() );
		}
		$m  = $cache[ $key ];
		$pv = (int) ( $m['days'][ $ymd ] ?? 0 );
		$out['days'][ $ymd ] = array( 'pv' => $pv, 'pocasi' => skalicka_weather_text( $ymd ), 'pozn' => skalicka_den_poznamka( $ymd ), 'wd' => (int) $d->format( 'N' ), 'clenstvi' => 0, 'nakup' => 0, 'rezervace' => 0 );
		$out['total']               += $pv;
		$out['wd'][ (int) $d->format( 'N' ) ] += $pv;
		$pref = $ymd . '|';
		foreach ( array( 'dp' => 'pages', 'dr' => 'refs', 'du' => 'utm', 'dk' => 'clicks' ) as $src => $dst ) {
			foreach ( (array) ( $m[ $src ] ?? array() ) as $k => $v ) {
				if ( 0 === strpos( $k, $pref ) ) {
					$kk                   = substr( $k, strlen( $pref ) );
					$out[ $dst ][ $kk ]   = ( $out[ $dst ][ $kk ] ?? 0 ) + $v;
					if ( 'dp' === $src && '/clenstvi/dekujeme/' === $kk ) {
						$out['days'][ $ymd ]['nakup'] += $v;
					}
					if ( 'dk' === $src && 'clenstvi_nakup' === $kk ) {
						$out['days'][ $ymd ]['clenstvi'] += $v;
					}
					if ( 'dk' === $src && 0 === strpos( $kk, 'rezervace_' ) ) {
						$out['days'][ $ymd ]['rezervace'] += $v;
					}
				}
			}
		}
		foreach ( (array) ( $m['dh'] ?? array() ) as $k => $v ) {
			if ( 0 === strpos( $k, $pref ) ) {
				$out['hours'][ (int) substr( $k, strlen( $pref ) ) ] += $v;
			}
		}
	}
	arsort( $out['pages'] );
	arsort( $out['refs'] );
	arsort( $out['utm'] );
	arsort( $out['clicks'] );
	return $out;
}

function skalicka_top_hours( array $hours, $n = 3 ) {
	arsort( $hours );
	$out = array();
	foreach ( array_slice( $hours, 0, $n, true ) as $h => $v ) {
		if ( $v > 0 ) {
			$out[] = $h . '–' . ( $h + 1 ) . ' h (' . $v . ')';
		}
	}
	return $out ? implode( ', ', $out ) : 'zatím málo dat';
}

/* ---------- E-mail ---------- */

function skalicka_report_html( $title, array $r, array $prev = null ) {
	$cz    = skalicka_dny_cz();
	$c     = $r['clicks'];
	$pg    = $r['pages'];
	$f_v   = (int) ( $pg['/clenstvi/'] ?? 0 );
	$f_c   = (int) ( $c['clenstvi_nakup'] ?? 0 );
	$f_d   = (int) ( $pg['/clenstvi/dekujeme/'] ?? 0 );
	$diff  = '';
	if ( $prev && $prev['total'] > 0 ) {
		$pct  = round( ( $r['total'] - $prev['total'] ) / $prev['total'] * 100 );
		$diff = ' <span style="color:' . ( $pct >= 0 ? '#1E8E52' : '#C8361F' ) . '">(' . ( $pct >= 0 ? '+' : '' ) . $pct . ' % proti předchozímu období)</span>';
	}
	$td   = 'style="padding:6px 8px;border-bottom:1px solid #eee;font-size:14px"';
	$h2   = 'style="font-size:17px;margin:22px 0 8px"';
	$html = '<div style="font-family:Arial,Helvetica,sans-serif;color:#3C3231;max-width:640px">'
		. '<h1 style="font-size:22px;margin:0 0 4px">' . esc_html( $title ) . '</h1>'
		. '<p style="font-size:15px;margin:0 0 16px">Zobrazení stránek: <b style="font-size:20px">' . (int) $r['total'] . '</b>' . $diff . '</p>';

	$html .= '<table style="border-collapse:collapse;width:100%"><tr>'
		. '<td style="background:#A3CBD6;padding:10px;border-radius:8px;text-align:center"><b style="font-size:20px">' . $f_v . '</b><br>Členství – otevřeli</td><td style="width:6px"></td>'
		. '<td style="background:#FECC06;padding:10px;border-radius:8px;text-align:center"><b style="font-size:20px">' . $f_c . '</b><br>Klikli „Stát se členem“</td><td style="width:6px"></td>'
		. '<td style="background:#DDF2D4;padding:10px;border-radius:8px;text-align:center"><b style="font-size:20px">' . $f_d . '</b><br>Dokončili nákup</td><td style="width:6px"></td>'
		. '<td style="background:#FBE1DC;padding:10px;border-radius:8px;text-align:center"><b style="font-size:20px">' . max( 0, $f_c - $f_d ) . '</b><br>Nedokončili</td>'
		. '</tr></table>';

	$labels = function_exists( 'skalicka_click_labels' ) ? skalicka_click_labels() : array();
	$html  .= '<h2 ' . $h2 . '>Kliky na důležitá tlačítka</h2>';
	if ( $c ) {
		$html .= '<table style="border-collapse:collapse;width:100%">';
		foreach ( array_slice( $c, 0, 8, true ) as $k => $v ) {
			$html .= '<tr><td ' . $td . '>' . esc_html( $labels[ $k ] ?? $k ) . '</td><td ' . $td . ' align="right"><b>' . (int) $v . '</b></td></tr>';
		}
		$html .= '</table>';
	} else {
		$html .= '<p>Zatím žádné.</p>';
	}

	$html .= '<h2 ' . $h2 . '>Po dnech</h2><table style="border-collapse:collapse;width:100%">'
		. '<tr><th ' . $td . ' align="left">Den</th><th ' . $td . ' align="right">Zobrazení</th><th ' . $td . ' align="left">Počasí</th><th ' . $td . ' align="left">Svátek / prázdniny</th></tr>';
	$max = max( 1, max( array_column( $r['days'], 'pv' ) ?: array( 1 ) ) );
	foreach ( $r['days'] as $ymd => $d ) {
		$we    = $d['wd'] >= 6;
		$bar   = '<div style="background:#A3CBD6;height:6px;border-radius:3px;width:' . (int) round( $d['pv'] / $max * 100 ) . '%"></div>';
		$html .= '<tr' . ( $we ? ' style="background:#FFF8F2"' : '' ) . '><td ' . $td . '>' . esc_html( $cz[ $d['wd'] ] . ' ' . wp_date( 'j. n.', strtotime( $ymd . ' 12:00' ) ) ) . '</td>'
			. '<td ' . $td . ' align="right"><b>' . (int) $d['pv'] . '</b>' . $bar . '</td><td ' . $td . '>' . esc_html( $d['pocasi'] ) . '</td><td ' . $td . '>' . esc_html( $d['pozn'] ) . '</td></tr>';
	}
	$html .= '</table>';

	$html .= '<h2 ' . $h2 . '>Kdy lidé chodí</h2><p style="font-size:14px">Nejsilnější hodiny: <b>' . esc_html( skalicka_top_hours( $r['hours'] ) ) . '</b></p>';

	foreach ( array( 'Nejnavštěvovanější stránky' => $r['pages'], 'Odkud lidé přišli' => $r['refs'], 'Kampaně (UTM)' => $r['utm'] ) as $t => $rows ) {
		if ( ! $rows ) {
			continue;
		}
		$html .= '<h2 ' . $h2 . '>' . esc_html( $t ) . '</h2><table style="border-collapse:collapse;width:100%">';
		foreach ( array_slice( $rows, 0, 8, true ) as $k => $v ) {
			$html .= '<tr><td ' . $td . '>' . esc_html( $k ) . '</td><td ' . $td . ' align="right"><b>' . (int) $v . '</b></td></tr>';
		}
		$html .= '</table>';
	}
	$html .= '<p style="font-size:13px;margin-top:22px"><a href="' . esc_url( admin_url( 'index.php?page=skalicka-stats' ) ) . '">Podrobná statistika ve WordPressu</a> (čas na stránkách, dočtení, všechny kliky).</p></div>';
	return $html;
}

function skalicka_report_csv( array $r ) {
	$cz  = skalicka_dny_cz();
	$csv = "\xEF\xBB\xBF" . "datum;den;zobrazeni;pocasi;svatek_prazdniny;klik_clenstvi;nakup_clenstvi;klik_rezervace\n";
	foreach ( $r['days'] as $ymd => $d ) {
		$csv .= implode( ';', array( $ymd, $cz[ $d['wd'] ], $d['pv'], $d['pocasi'], $d['pozn'], $d['clenstvi'], $d['nakup'], $d['rezervace'] ) ) . "\n";
	}
	return $csv;
}

function skalicka_report_send( $type, $to = '' ) {
	$tz    = wp_timezone();
	$today = new DateTimeImmutable( 'today', $tz );
	$to    = $to ? $to : skalicka_opt( 'report_email' );
	if ( ! is_email( $to ) ) {
		$to = 'info@svetskalicka.cz';
	}
	skalicka_weather_update( 31 );
	$attach = array();
	if ( 'mesic' === $type ) {
		$from  = $today->modify( 'first day of last month' );
		$end   = $today->modify( 'last day of last month' );
		$pfrom = $from->modify( 'first day of last month' );
		$pend  = $from->modify( '-1 day' );
		$title = 'Web Skalička – ' . wp_date( 'F Y', $from->getTimestamp() );
	} elseif ( 'nahled' === $type ) {
		$end   = $today->modify( '-1 day' );
		$from  = $end->modify( '-6 days' );
		$pend  = $from->modify( '-1 day' );
		$pfrom = $pend->modify( '-6 days' );
		$title = 'Web Skalička – posledních 7 dní (ukázka)';
	} else {
		$from  = $today->modify( 'monday last week' );
		$end   = $from->modify( '+6 days' );
		$pfrom = $from->modify( '-7 days' );
		$pend  = $from->modify( '-1 day' );
		$title = 'Web Skalička – týden ' . $from->format( 'j. n.' ) . '–' . $end->format( 'j. n. Y' );
	}
	$r    = skalicka_report_data( $from, $end );
	$prev = skalicka_report_data( $pfrom, $pend );
	$html = skalicka_report_html( $title, $r, $prev );
	if ( 'mesic' === $type ) {
		$file = trailingslashit( get_temp_dir() ) . 'skalicka-navstevnost-' . $from->format( 'Y-m' ) . '.csv';
		file_put_contents( $file, skalicka_report_csv( $r ) ); // phpcs:ignore
		$attach[] = $file;
	}
	$domain = wp_parse_url( home_url(), PHP_URL_HOST );
	$ok     = wp_mail( $to, $title . ': ' . (int) $r['total'] . ' zobrazení', $html, array( 'Content-Type: text/html; charset=UTF-8', 'From: Web Dětský svět Skalička <web@' . $domain . '>' ), $attach );
	foreach ( $attach as $f ) {
		@unlink( $f ); // phpcs:ignore
	}
	return $ok;
}

/* ---------- Plánování: jednou denně ráno ---------- */

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'skalicka_denni' ) ) {
		$tz   = wp_timezone();
		$next = new DateTimeImmutable( 'tomorrow 06:40', $tz );
		wp_schedule_event( $next->getTimestamp(), 'daily', 'skalicka_denni' );
		skalicka_weather_update( 14 );
	}
} );

add_action( 'skalicka_denni', function () {
	skalicka_weather_update( 7 );
	$today = wp_date( 'Y-m-d' );
	$sent  = (array) get_option( 'skalicka_report_odeslano', array() );
	if ( '1' === wp_date( 'N' ) && ( $sent['tyden'] ?? '' ) !== $today ) {
		if ( skalicka_report_send( 'tyden' ) ) {
			$sent['tyden'] = $today;
		}
	}
	if ( '01' === wp_date( 'd' ) && ( $sent['mesic'] ?? '' ) !== $today ) {
		if ( skalicka_report_send( 'mesic' ) ) {
			$sent['mesic'] = $today;
		}
	}
	update_option( 'skalicka_report_odeslano', $sent, false );
} );

/* Tlačítko „Poslat ukázku reportu“ ve statistice. */
add_action( 'admin_post_skalicka_report_nahled', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Nemáte oprávnění.' );
	}
	check_admin_referer( 'skalicka_report_nahled' );
	$ok = skalicka_report_send( 'nahled' );
	wp_safe_redirect( add_query_arg( 'report', $ok ? 'ok' : 'chyba', admin_url( 'index.php?page=skalicka-stats' ) ) );
	exit;
} );

/* ---------- Doplňkové sekce ve statistice ---------- */

function skalicka_fmt_sec( $s ) {
	$s = (int) round( $s );
	return $s >= 60 ? intdiv( $s, 60 ) . ' min ' . ( $s % 60 ) . ' s' : $s . ' s';
}

add_action( 'skalicka_stats_extra', function ( $data, $month ) {
	$cz = skalicka_dny_cz();
	// Hodiny a dny v týdnu
	$hours = array_fill( 0, 24, 0 );
	$wd    = array_fill( 1, 7, 0 );
	foreach ( (array) ( $data['dh'] ?? array() ) as $k => $v ) {
		list( $d, $h ) = explode( '|', $k );
		$hours[ (int) $h ] += $v;
	}
	foreach ( (array) ( $data['days'] ?? array() ) as $d => $v ) {
		$wd[ (int) wp_date( 'N', strtotime( $d . ' 12:00' ) ) ] += $v;
	}
	echo '<h2>Kdy lidé chodí – hodiny</h2>';
	if ( array_sum( $hours ) ) {
		$max = max( 1, max( $hours ) );
		echo '<div style="display:flex;align-items:flex-end;gap:3px;height:120px;max-width:720px;border-bottom:1px solid #ccc">';
		foreach ( $hours as $h => $v ) {
			echo '<div title="' . esc_attr( $h . ':00 – ' . $v ) . '" style="flex:1;background:#FECC06;height:' . (int) round( $v / $max * 100 ) . '%"></div>';
		}
		echo '</div><div style="display:flex;max-width:720px;font-size:11px;color:#646970">';
		for ( $h = 0; $h < 24; $h++ ) {
			echo '<div style="flex:1;text-align:center">' . ( 0 === $h % 3 ? (int) $h : '' ) . '</div>';
		}
		echo '</div><p>Nejsilnější: <strong>' . esc_html( skalicka_top_hours( $hours ) ) . '</strong></p>';
	} else {
		echo '<p>Měří se od 5. 11. 2026.</p>';
	}
	echo '<h2>Dny v týdnu</h2><table class="widefat striped" style="max-width:720px"><tbody><tr>';
	foreach ( $wd as $n => $v ) {
		echo '<td style="text-align:center"><b>' . esc_html( $cz[ $n ] ) . '</b><br>' . (int) $v . '</td>';
	}
	echo '</tr></tbody></table>';

	// Čas na stránce a dočtení
	$cnt = (array) ( $data['tcnt'] ?? array() );
	echo '<h2>Čas na stránce a dočtení</h2>';
	if ( $cnt ) {
		arsort( $cnt );
		echo '<table class="widefat striped" style="max-width:720px"><thead><tr><th>Stránka</th><th style="text-align:right">Průměrný čas</th><th style="text-align:right">Dočetlo do půlky</th><th style="text-align:right">Dočetlo do konce</th><th style="text-align:right">Měřeno</th></tr></thead><tbody>';
		foreach ( array_slice( $cnt, 0, 15, true ) as $p => $n ) {
			$sum = (int) ( $data['tsum'][ $p ] ?? 0 );
			echo '<tr><td>' . esc_html( $p ) . '</td><td style="text-align:right">' . esc_html( skalicka_fmt_sec( $sum / max( 1, $n ) ) ) . '</td><td style="text-align:right">' . (int) round( ( $data['scr50'][ $p ] ?? 0 ) / $n * 100 ) . ' %</td><td style="text-align:right">' . (int) round( ( $data['scr90'][ $p ] ?? 0 ) / $n * 100 ) . ' %</td><td style="text-align:right">' . (int) $n . '×</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>Měří se od 5. 11. 2026.</p>';
	}

	skalicka_stats_table( 'Na co lidé klikají (stránka › odkaz nebo tlačítko)', (array) ( $data['clk'] ?? array() ), array(), 25 );
	skalicka_stats_table( 'Kampaně (odkazy s UTM)', (array) ( $data['utm'] ?? array() ) );

	// Dny s počasím
	$days = (array) ( $data['days'] ?? array() );
	ksort( $days );
	echo '<h2>Dny, počasí a svátky</h2><table class="widefat striped" style="max-width:720px"><thead><tr><th>Den</th><th style="text-align:right">Zobrazení</th><th>Počasí</th><th>Svátek / prázdniny</th></tr></thead><tbody>';
	foreach ( $days as $d => $v ) {
		$n = (int) wp_date( 'N', strtotime( $d . ' 12:00' ) );
		echo '<tr><td>' . esc_html( $cz[ $n ] . ' ' . wp_date( 'j. n.', strtotime( $d . ' 12:00' ) ) ) . '</td><td style="text-align:right"><b>' . (int) $v . '</b></td><td>' . esc_html( skalicka_weather_text( $d ) ) . '</td><td>' . esc_html( skalicka_den_poznamka( $d ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	// Report
	echo '<h2>Report e-mailem</h2><p>Chodí každé pondělí ráno za minulý týden a prvního v měsíci za celý měsíc (s tabulkou CSV) na <strong>' . esc_html( skalicka_opt( 'report_email' ) ) . '</strong>. Adresu změníte v Nastavení herny.</p>';
	if ( isset( $_GET['report'] ) ) {
		echo '<p><strong>' . ( 'ok' === $_GET['report'] ? 'Ukázka reportu odeslána.' : 'Report se nepodařilo odeslat.' ) . '</strong></p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="skalicka_report_nahled">';
	wp_nonce_field( 'skalicka_report_nahled' );
	echo '<button class="button button-primary">Poslat ukázku reportu teď</button></form>';
}, 10, 2 );
