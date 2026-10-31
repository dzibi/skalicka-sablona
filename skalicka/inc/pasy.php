<?php
/**
 * Barevné pásy i na ostatních stránkách: obsah se rozdělí podle nadpisů H2
 * a každá část dostane vlastní barvu s obloukem (jako na úvodní stránce).
 * Právní dokumenty zůstávají bílé, kvůli čitelnosti.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_plain_pages() {
	return array( 'zasady-ochrany-osobnich-udaju', 'podminky-clenstvi', 'provozni-rad', 'reklamacni-rad', 'cookies' );
}

function skalicka_is_banded_page() {
	if ( ! is_page() || is_front_page() ) {
		return false;
	}
	$post = get_post();
	return $post && ! in_array( $post->post_name, skalicka_plain_pages(), true );
}

function skalicka_band_colors() {
	return array( 'krem', 'mint', 'bila', 'zluta', 'ruzova', 'modra' );
}

/** Rozdělí hotové HTML stránky na pásy podle H2 na nejvyšší úrovni. */
function skalicka_band_split( $html ) {
	if ( false === stripos( $html, '<h2' ) || ! class_exists( 'DOMDocument' ) ) {
		return '';
	}
	$prev = libxml_use_internal_errors( true );
	$doc  = new DOMDocument();
	$doc->loadHTML( '<?xml encoding="utf-8" ?><div id="sk-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	$root = $doc->getElementById( 'sk-root' );
	if ( ! $root ) {
		return '';
	}

	$sections = array( '' );
	foreach ( iterator_to_array( $root->childNodes ) as $node ) {
		if ( XML_ELEMENT_NODE === $node->nodeType && 'h2' === strtolower( $node->nodeName ) ) {
			$sections[] = '';
		}
		$sections[ count( $sections ) - 1 ] .= $doc->saveHTML( $node );
	}
	if ( count( $sections ) < 2 ) {
		return '';
	}

	$colors = skalicka_band_colors();
	$out    = '';
	$i      = 0;
	foreach ( $sections as $n => $part ) {
		if ( '' === trim( wp_strip_all_tags( $part, true ) ) && false === stripos( $part, '<img' ) && false === stripos( $part, '<video' ) ) {
			continue;
		}
		$color = 0 === $n ? 'bila' : $colors[ $i++ % count( $colors ) ];
		$out  .= '<section class="sk-pband sk-bg-' . $color . '"><div class="sk-page sk-pband-in">' . $part . '</div></section>';
	}
	return $out;
}

/** Vypíše obsah stránky: s pásy, nebo klasicky (právní dokumenty, stránky bez H2). */
function skalicka_page_content() {
	$html = apply_filters( 'the_content', get_the_content() );
	$html = str_replace( ']]>', ']]&gt;', $html );
	if ( skalicka_is_banded_page() ) {
		$bands = skalicka_band_split( $html );
		if ( $bands ) {
			echo '<div class="sk-pbands">' . $bands . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- obsah stránky prošel the_content
			return;
		}
	}
	echo '<article class="sk-page">' . $html . '</article>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
