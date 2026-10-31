<?php
/**
 * Ilustrace v modré hlavičce podstránek (jako balónky u oslav).
 * Každá stránka má vlastní obrázek, který se lehce pohupuje.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_page_art( $slug ) {
	$s = "stroke='#3C3231' stroke-width='3.5' stroke-linejoin='round' stroke-linecap='round'";
	$art = array(
		// Herna: bazének s míčky
		'herna-a-vstupne' => "<path d='M14 92h132v28a10 10 0 0 1-10 10H24a10 10 0 0 1-10-10z' fill='#FFF8F2' $s/>"
			. "<circle cx='40' cy='84' r='15' fill='#E84F37' $s/><circle cx='70' cy='78' r='15' fill='#FECC06' $s/><circle cx='100' cy='84' r='15' fill='#A3CBD6' $s/><circle cx='126' cy='80' r='13' fill='#B2DCD9' $s/><circle cx='56' cy='58' r='13' fill='#FD8F6B' $s/><circle cx='88' cy='54' r='13' fill='#D3B2A8' $s/>"
			. "<circle cx='35' cy='79' r='4' fill='#fff' opacity='.7'/><circle cx='65' cy='73' r='4' fill='#fff' opacity='.7'/><circle cx='95' cy='79' r='4' fill='#fff' opacity='.7'/>",
		// Lekce: noty a hvězda
		'lekce'           => "<path d='M40 104V40l58-14v62' fill='none' $s/><path d='M40 40l58-14v14L40 54z' fill='#3C3231' $s/>"
			. "<ellipse cx='30' cy='106' rx='13' ry='10' fill='#FECC06' $s/><ellipse cx='88' cy='92' rx='13' ry='10' fill='#FD8F6B' $s/>"
			. "<path d='M126 22l6 13 14 2-10 10 3 14-13-7-13 7 3-14-10-10 14-2z' fill='#B2DCD9' $s/>",
		// Oslavy: balónky
		'oslavy'          => "<path d='M50 84 Q46 108 60 128M82 76 Q88 104 72 128M112 86 Q110 110 84 128' fill='none' $s/>"
			. "<ellipse cx='50' cy='54' rx='24' ry='30' fill='#FECC06' $s/><ellipse cx='82' cy='44' rx='25' ry='31' fill='#E84F37' $s/><ellipse cx='112' cy='58' rx='22' ry='28' fill='#B2DCD9' $s/>"
			. "<ellipse cx='42' cy='42' rx='5' ry='9' fill='#fff' opacity='.7'/><ellipse cx='74' cy='31' rx='5' ry='9' fill='#fff' opacity='.7'/>",
		// Členství: vstupenka s hvězdou
		'clenstvi'        => "<path d='M18 44h124v18a12 12 0 0 0 0 24v18H18V86a12 12 0 0 0 0-24z' fill='#FECC06' $s/><path d='M58 50v54' fill='none' stroke='#3C3231' stroke-width='3' stroke-dasharray='5 6'/>"
			. "<path d='M100 54l6 12 13 2-9 9 2 13-12-6-12 6 2-13-9-9 13-2z' fill='#fff' $s/><circle cx='38' cy='74' r='8' fill='#E84F37' $s/>",
		// Návštěva: špendlík na mapě
		'navsteva'        => "<path d='M18 112l40-14 44 14 40-14v28l-40 14-44-14-40 14z' fill='#B2DCD9' $s/><path d='M58 98v28M102 112v28' fill='none' $s/>"
			. "<path d='M80 106C60 80 50 64 50 50a30 30 0 0 1 60 0c0 14-10 30-30 56z' fill='#E84F37' $s/><circle cx='80' cy='50' r='11' fill='#FFF8F2' $s/>",
		// Kontakt: obálka a bublina
		'kontakt'         => "<rect x='16' y='54' width='92' height='64' rx='8' fill='#FFF8F2' $s/><path d='M16 60l46 34 46-34' fill='none' $s/>"
			. "<path d='M100 18h44a10 10 0 0 1 10 10v24a10 10 0 0 1-10 10h-26l-14 12V62h-4a10 10 0 0 1-10-10V28a10 10 0 0 1 10-10z' fill='#FECC06' $s/><circle cx='112' cy='40' r='3.5' fill='#3C3231'/><circle cx='124' cy='40' r='3.5' fill='#3C3231'/><circle cx='136' cy='40' r='3.5' fill='#3C3231'/>",
		// Kavárna: hrnek s párou
		'kavarna-a-menu'  => "<path d='M34 62h78v34a26 26 0 0 1-26 26H60a26 26 0 0 1-26-26z' fill='#FFF8F2' $s/><path d='M112 72h8a14 14 0 0 1 0 28h-10' fill='none' $s/><path d='M24 126h108' fill='none' $s/>"
			. "<path d='M56 48c-6-8 6-12 0-22M74 48c-6-8 6-12 0-22M92 48c-6-8 6-12 0-22' fill='none' stroke='#C08552' stroke-width='3.5' stroke-linecap='round'/><path d='M50 80h46' fill='none' stroke='#C08552' stroke-width='8' stroke-linecap='round'/>",
		// Školky a školy: batoh
		'skolky-a-skoly'  => "<path d='M50 46h60a16 16 0 0 1 16 16v54a10 10 0 0 1-10 10H44a10 10 0 0 1-10-10V62a16 16 0 0 1 16-16z' fill='#FD8F6B' $s/><path d='M62 46v-8a18 18 0 0 1 36 0v8' fill='none' $s/>"
			. "<rect x='50' y='78' width='60' height='32' rx='8' fill='#FECC06' $s/><path d='M70 78v10M90 78v10' fill='none' $s/>",
	);
	if ( ! isset( $art[ $slug ] ) ) {
		return '';
	}
	return '<div class="sk-pagehead-art" aria-hidden="true"><svg viewBox="0 0 160 145" xmlns="http://www.w3.org/2000/svg">' . $art[ $slug ] . '</svg></div>';
}

/**
 * [skalicka_vytah] – řez obchodním centrem: hlavní výtah z garáží a dva větší výtahy z přízemí
 * a 2. patra jezdí nahoru do 3. patra k herně.
 */
add_shortcode( 'skalicka_vytah', function () {
	$floors = array(
		array( 46, '3. patro', 'herna Dětský svět Skalička' ),
		array( 106, '2. patro', 'u Alzaboxu, za Fio bankou' ),
		array( 166, 'Přízemí', 'za Pepcem' ),
		array( 226, 'Garáže', 'parkování' ),
	);
	$svg  = '<svg viewBox="0 0 520 260" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="sk-vytah-t">';
	$svg .= '<title id="sk-vytah-t">Výtahy k herně: hlavní výtah u schodiště jede z garáží do 3. patra, dva větší výtahy v zadní části OC jezdí z přízemí (za Pepcem) a z 2. patra (boční vchod u Alzaboxu, za Fio bankou vpravo) přímo k herně.</title>';
	$svg .= '<rect x="8" y="8" width="504" height="244" rx="22" fill="#FFF8F2" stroke="#3C3231" stroke-width="3"/>';
	foreach ( $floors as $f ) {
		$svg .= '<line x1="20" x2="500" y1="' . $f[0] . '" y2="' . $f[0] . '" stroke="#3C3231" stroke-width="3" stroke-linecap="round" opacity=".85"/>';
		$svg .= '<text x="26" y="' . ( $f[0] - 24 ) . '" font-family="Nunito, sans-serif" font-weight="800" font-size="17" fill="#3C3231">' . esc_html( $f[1] ) . '</text>';
		$svg .= '<text x="26" y="' . ( $f[0] - 7 ) . '" font-family="Nunito, sans-serif" font-weight="600" font-size="14" fill="#3C3231" opacity=".8">' . esc_html( $f[2] ) . '</text>';
	}
	// šachty
	$svg .= '<rect x="250" y="16" width="52" height="214" rx="8" fill="#A3CBD6" opacity=".45" stroke="#3C3231" stroke-width="2.5" stroke-dasharray="6 6"/>';
	$svg .= '<rect x="370" y="16" width="52" height="154" rx="8" fill="#B2DCD9" opacity=".55" stroke="#3C3231" stroke-width="2.5" stroke-dasharray="6 6"/>';
	$svg .= '<text x="276" y="250" text-anchor="middle" font-family="Nunito, sans-serif" font-weight="800" font-size="13" fill="#3C3231">hlavní u schodiště</text>';
	$svg .= '<text x="396" y="190" text-anchor="middle" font-family="Nunito, sans-serif" font-weight="800" font-size="13" fill="#3C3231">2 větší výtahy</text>';
	// kabiny (pohyb řeší CSS)
	$cab = function ( $x, $cls, $color ) {
		return '<g class="sk-cab ' . $cls . '"><rect x="' . $x . '" y="0" width="40" height="38" rx="7" fill="' . $color . '" stroke="#3C3231" stroke-width="3"/>'
			. '<line x1="' . ( $x + 20 ) . '" x2="' . ( $x + 20 ) . '" y1="4" y2="34" stroke="#3C3231" stroke-width="2"/>'
			. '<circle cx="' . ( $x + 12 ) . '" cy="14" r="5" fill="#F6CBA4" stroke="#3C3231" stroke-width="1.8"/><circle cx="' . ( $x + 28 ) . '" cy="16" r="4" fill="#F6CBA4" stroke="#3C3231" stroke-width="1.8"/></g>';
	};
	$svg .= $cab( 256, 'sk-cab-1', '#FECC06' );
	$svg .= $cab( 376, 'sk-cab-2', '#FD8F6B' );
	// cíl: herna
	$svg .= '<g class="sk-cil"><path d="M462 40 L462 18 L478 8 L494 18 L494 40 Z" fill="#E84F37" stroke="#3C3231" stroke-width="3" stroke-linejoin="round"/><rect x="473" y="27" width="10" height="13" fill="#FFF8F2" stroke="#3C3231" stroke-width="2"/></g>';
	$svg .= '<path class="sk-sipka" d="M310 30 H440" stroke="#3C3231" stroke-width="3" stroke-dasharray="4 7" fill="none" stroke-linecap="round"/><path d="M436 24 L446 30 L436 36" stroke="#3C3231" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
	$svg .= '</svg>';
	return '<figure class="sk-vytah">' . $svg . '<figcaption>Z garáží hlavním výtahem u schodiště, z obchodního centra dvěma většími výtahy přímo k herně.</figcaption></figure>';
} );
