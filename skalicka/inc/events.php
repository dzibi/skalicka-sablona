<?php
/**
 * Napojení na kalendář The Events Calendar (typ obsahu tribe_events).
 * Čte začátek a konec akcí z polí _EventStartDate / _EventEndDate (místní čas webu).
 * Bez pluginu vrací prázdné výsledky, web funguje dál.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Kategorie kalendáře, se kterými šablona počítá (adresa kategorie). */
const SKALICKA_CAT_CLOSED    = 'zavreno';
const SKALICKA_CAT_POHYBOVKY = 'pohybovky';

function skalicka_events_available() {
	return post_type_exists( 'tribe_events' );
}

/**
 * Akce, které se překrývají s intervalem [$from, $to).
 *
 * @return array<int, array{id:int,title:string,start:DateTimeImmutable,end:DateTimeImmutable,allday:bool,cats:string[]}>
 */
function skalicka_events_between( DateTimeImmutable $from, DateTimeImmutable $to, $cat = null, $limit = 50, $exclude_cat = null ) {
	if ( ! skalicka_events_available() ) {
		return array();
	}
	$args = array(
		'post_type'        => 'tribe_events',
		'post_status'      => 'publish',
		'posts_per_page'   => $limit,
		'no_found_rows'    => true,
		'suppress_filters' => true,
		'meta_key'         => '_EventStartDate',
		'orderby'          => 'meta_value',
		'order'            => 'ASC',
		'meta_query'       => array(
			'relation' => 'AND',
			array(
				'key'     => '_EventStartDate',
				'value'   => $to->format( 'Y-m-d H:i:s' ),
				'compare' => '<',
				'type'    => 'DATETIME',
			),
			array(
				'key'     => '_EventEndDate',
				'value'   => $from->format( 'Y-m-d H:i:s' ),
				'compare' => '>',
				'type'    => 'DATETIME',
			),
		),
	);
	$tax = array();
	if ( $cat ) {
		$tax[] = array(
			'taxonomy' => 'tribe_events_cat',
			'field'    => 'slug',
			'terms'    => (array) $cat,
		);
	}
	if ( $exclude_cat ) {
		$tax[] = array(
			'taxonomy' => 'tribe_events_cat',
			'field'    => 'slug',
			'terms'    => (array) $exclude_cat,
			'operator' => 'NOT IN',
		);
	}
	if ( $tax ) {
		$args['tax_query'] = $tax;
	}

	$q   = new WP_Query( $args );
	$tz  = wp_timezone();
	$out = array();
	foreach ( $q->posts as $p ) {
		$s = get_post_meta( $p->ID, '_EventStartDate', true );
		$e = get_post_meta( $p->ID, '_EventEndDate', true );
		if ( ! $s || ! $e ) {
			continue;
		}
		$terms = get_the_terms( $p->ID, 'tribe_events_cat' );
		$out[] = array(
			'id'     => $p->ID,
			'title'  => get_the_title( $p ),
			'start'  => new DateTimeImmutable( $s, $tz ),
			'end'    => new DateTimeImmutable( $e, $tz ),
			'allday' => 'yes' === strtolower( (string) get_post_meta( $p->ID, '_EventAllDay', true ) ),
			'cats'   => ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'slug' ) : array(),
		);
	}
	return $out;
}

/** Nadcházející akce (začínají od teď). */
function skalicka_upcoming_events( $cat, $limit = 3 ) {
	$tz   = wp_timezone();
	$now  = new DateTimeImmutable( 'now', $tz );
	$list = skalicka_events_between( $now, $now->modify( '+6 months' ), $cat, $limit * 3 );
	$list = array_values( array_filter( $list, function ( $e ) use ( $now ) {
		return $e['start'] >= $now;
	} ) );
	return array_slice( $list, 0, $limit );
}

/**
 * Zavírací intervaly v daném dni v minutách od půlnoci, pro semafor.
 *
 * @return array<int, array{from:int,to:int,label:string}>
 */
function skalicka_day_closures( DateTimeImmutable $day ) {
	$start = $day->setTime( 0, 0 );
	$end   = $start->modify( '+1 day' );
	$out   = array();
	foreach ( skalicka_events_between( $start, $end, SKALICKA_CAT_CLOSED ) as $e ) {
		if ( $e['allday'] ) {
			$from = 0;
			$to   = 1440;
		} else {
			$from = $e['start'] <= $start ? 0 : ( (int) $e['start']->format( 'G' ) * 60 + (int) $e['start']->format( 'i' ) );
			$to   = $e['end'] >= $end ? 1440 : ( (int) $e['end']->format( 'G' ) * 60 + (int) $e['end']->format( 'i' ) );
		}
		$out[] = array( 'from' => $from, 'to' => $to, 'label' => $e['title'] );
	}
	return $out;
}

/** Je den celý zavřený (celodenní akce v kategorii Zavřeno)? */
function skalicka_day_closed_all( DateTimeImmutable $day ) {
	foreach ( skalicka_day_closures( $day ) as $c ) {
		if ( $c['from'] <= 0 && $c['to'] >= 1440 ) {
			return true;
		}
	}
	return false;
}

/**
 * Zavírání z kalendáře na dalších N dní (od zítřka), jen dny, kde něco je.
 *
 * @return array<string, array<int, array{from:int,to:int}>> klíč Y-m-d
 */
function skalicka_closures_ahead( DateTimeImmutable $today, $days = 7 ) {
	$out = array();
	for ( $i = 1; $i <= $days; $i++ ) {
		$d  = $today->modify( '+' . $i . ' day' );
		$cl = skalicka_day_closures( $d );
		if ( $cl ) {
			$out[ $d->format( 'Y-m-d' ) ] = array_map( function ( $c ) {
				return array( 'from' => $c['from'], 'to' => $c['to'] );
			}, $cl );
		}
	}
	return (object) $out;
}

/* ---------- Pomocné české názvy ---------- */

function skalicka_month_gen( $m ) {
	$names = array( 1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince' );
	return $names[ (int) $m ];
}

function skalicka_weekday_short( DateTimeInterface $d ) {
	$names = array( 1 => 'PO', 'ÚT', 'ST', 'ČT', 'PÁ', 'SO', 'NE' );
	return $names[ (int) $d->format( 'N' ) ];
}

function skalicka_time( DateTimeInterface $d ) {
	return $d->format( 'G:i' );
}

/* Kalendář otevírat rovnou v měsíčním pohledu (na počítači i mobilu), dny s akcí jsou barevné. */
add_filter( 'tribe_get_option_viewOption', function () { return 'month'; } );
add_filter( 'tribe_get_option_mobile_default_view', function () { return 'month'; } );
