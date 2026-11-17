<?php
/**
 * Menu kavárny: položky a sekce spravované v administraci (Menu kavárny),
 * rychlá úprava cen na jedné stránce a výpis na stránce /menu/ (šablona page-menu.php).
 *
 * Položka = vlastní typ obsahu „sk_polozka“ (název, cena, popis, štítek, fotka, pořadí).
 * Skrytá položka = koncept. Sekce = taxonomie „sk_sekce“ s pořadím, skupinou (Pití / Jídlo a sladké),
 * ikonou a poznámkou (popis rubriky).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SKALICKA_MENU_PT  = 'sk_polozka';
const SKALICKA_MENU_TAX = 'sk_sekce';

/* ---------- Typ obsahu a sekce ---------- */

add_action( 'init', function () {
	register_post_type( SKALICKA_MENU_PT, array(
		'labels'          => array(
			'name'               => 'Menu kavárny',
			'singular_name'      => 'Položka menu',
			'menu_name'          => 'Menu kavárny',
			'all_items'          => 'Všechny položky',
			'add_new'            => 'Přidat položku',
			'add_new_item'       => 'Přidat položku menu',
			'edit_item'          => 'Upravit položku menu',
			'new_item'           => 'Nová položka',
			'search_items'       => 'Hledat v menu',
			'not_found'          => 'Žádné položky.',
			'not_found_in_trash' => 'V koši nic není.',
			'featured_image'     => 'Fotka (bez pozadí)',
			'set_featured_image' => 'Vybrat fotku',
			'remove_featured_image' => 'Odebrat fotku',
			'use_featured_image' => 'Použít jako fotku',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'show_in_rest'    => false,
		'menu_position'   => 21,
		'menu_icon'       => 'dashicons-coffee',
		'supports'        => array( 'title', 'thumbnail', 'page-attributes' ),
		'capability_type' => 'page',
		'map_meta_cap'    => true,
		'rewrite'         => false,
		'query_var'       => false,
	) );

	register_taxonomy( SKALICKA_MENU_TAX, SKALICKA_MENU_PT, array(
		'labels'            => array(
			'name'          => 'Sekce menu',
			'singular_name' => 'Sekce',
			'menu_name'     => 'Sekce',
			'all_items'     => 'Všechny sekce',
			'edit_item'     => 'Upravit sekci',
			'add_new_item'  => 'Přidat sekci',
			'new_item_name' => 'Název nové sekce',
			'search_items'  => 'Hledat sekce',
			'not_found'     => 'Žádné sekce.',
		),
		'public'            => false,
		'show_ui'           => true,
		'show_in_rest'      => false,
		'show_admin_column' => true,
		'hierarchical'      => true,
		'rewrite'           => false,
		'query_var'         => false,
		'capabilities'      => array(
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_pages',
		),
	) );
} );

/** Ikony u položek bez fotky (podle sekce, jde změnit u položky). */
function skalicka_menu_icons() {
	return array(
		'kava'      => 'Šálek kávy',
		'caj'       => 'Hrnek čaje',
		'sklenice'  => 'Sklenice s limonádou',
		'vino'      => 'Sklenička',
		'toast'     => 'Toast',
		'pizza'     => 'Pizza',
		'salat'     => 'Miska',
		'deti'      => 'Dětský nápoj',
		'vafle'     => 'Vafle',
		'dort'      => 'Dort',
		'zmrzlina'  => 'Zmrzlina',
	);
}

/** Inline SVG ikona ve stylu ilustrací webu. */
function skalicka_menu_icon_svg( $key ) {
	$s = "stroke='#3C3231' stroke-width='3' stroke-linejoin='round' stroke-linecap='round'";
	$i = array(
		'kava'     => "<path d='M12 26h32v10a16 16 0 0 1-16 16h0a16 16 0 0 1-16-16z' fill='#FFF8F2' $s/><path d='M44 30h3a7 7 0 0 1 0 14h-5' fill='none' $s/><path d='M8 56h40' fill='none' $s/><path d='M22 19c-3-4 3-6 0-11M32 19c-3-4 3-6 0-11' fill='none' stroke='#C08552' stroke-width='3' stroke-linecap='round'/><path d='M18 33h20' stroke='#C08552' stroke-width='5' stroke-linecap='round'/>",
		'caj'      => "<path d='M14 22h30v20a12 12 0 0 1-12 12h-6a12 12 0 0 1-12-12z' fill='#B2DCD9' $s/><path d='M44 27h3a6 6 0 0 1 0 12h-3' fill='none' $s/><path d='M30 22V10' fill='none' stroke='#3C3231' stroke-width='2.5'/><rect x='24' y='30' width='12' height='10' rx='2' fill='#FECC06' $s/>",
		'sklenice' => "<path d='M16 14h30l-4 40a5 5 0 0 1-5 4H25a5 5 0 0 1-5-4z' fill='#FFF1B8' $s/><path d='M18 30h26' fill='none' stroke='#FD8F6B' stroke-width='3'/><path d='M36 14l8-10' fill='none' $s/><circle cx='26' cy='40' r='6' fill='#FECC06' $s/><path d='M26 34v12M20 40h12' stroke='#3C3231' stroke-width='1.5'/>",
		'vino'     => "<path d='M18 8h28c0 18-5 26-14 26S18 26 18 8z' fill='#E9B679' $s/><path d='M19 16h26' fill='none' stroke='#3C3231' stroke-width='2'/><path d='M32 34v18M22 56h20' fill='none' $s/>",
		'toast'    => "<path d='M8 46L32 12l24 34z' fill='#E9B679' $s/><path d='M14 42h36' fill='none' stroke='#FECC06' stroke-width='5' stroke-linecap='round'/><path d='M8 50h48' fill='none' $s/><path d='M24 28l6 6M32 22l6 6M30 34l6 6' stroke='#C08552' stroke-width='3' stroke-linecap='round'/>",
		'pizza'    => "<path d='M8 16Q32 4 56 16L32 58z' fill='#FECC06' $s/><path d='M8 16Q32 4 56 16' fill='none' stroke='#C08552' stroke-width='7' stroke-linecap='round'/><circle cx='26' cy='26' r='5' fill='#E84F37' $s/><circle cx='38' cy='30' r='4' fill='#E84F37' $s/><circle cx='32' cy='42' r='4' fill='#E84F37' $s/>",
		'salat'    => "<path d='M16 28c0-8 6-14 12-12 2-6 12-6 14 0 6-2 10 4 8 12' fill='#9BD58A' $s/><path d='M6 30h52a26 22 0 0 1-52 0z' fill='#FFF8F2' $s/><circle cx='24' cy='26' r='4' fill='#E84F37' $s/>",
		'deti'     => "<rect x='16' y='16' width='28' height='40' rx='5' fill='#FD8F6B' $s/><path d='M38 16V6h8' fill='none' $s/><path d='M30 28l3 6 6 1-4 4 1 6-6-3-6 3 1-6-4-4 6-1z' fill='#FECC06' $s/>",
		'vafle'    => "<rect x='10' y='14' width='44' height='38' rx='9' fill='#E9B679' $s/><path d='M24 15v36M40 15v36M11 27h42M11 39h42' fill='none' stroke='#C08552' stroke-width='3'/><path d='M38 12c0-6 10-6 10 0 4 0 4 6 0 6h-10c-4 0-4-6 0-6z' fill='#fff' $s/>",
		'dort'     => "<path d='M8 44l46-14v20H8z' fill='#FFF8F2' $s/><path d='M8 44l46-14' fill='none' $s/><path d='M8 50h46' fill='none' stroke='#FD8F6B' stroke-width='5'/><path d='M8 44V56h46V30' fill='none' $s/><circle cx='42' cy='26' r='5' fill='#E84F37' $s/><path d='M42 21c0-5 4-7 6-8' fill='none' stroke='#3C3231' stroke-width='2.5'/>",
		'zmrzlina' => "<path d='M22 32l10 26 10-26' fill='#E9B679' $s/><path d='M26 38l12 0M28 46h8' stroke='#C08552' stroke-width='2.5'/><circle cx='32' cy='22' r='13' fill='#FD8F6B' $s/><circle cx='27' cy='18' r='3' fill='#fff' opacity='.7'/>",
	);
	$k = isset( $i[ $key ] ) ? $key : 'kava';
	return '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' . $i[ $k ] . '</svg>';
}

/* ---------- Pole položky ---------- */

/** Výchozí návrhy štítků (jde napsat i vlastní). */
function skalicka_menu_badges() {
	return array( 'Oblíbené', 'Novinka', 'Sezóna', 'Zvýhodněně', 'Ušetříte až 21 Kč' );
}

/** Barva štítku podle textu. */
function skalicka_menu_badge_class( $text ) {
	$t = mb_strtolower( (string) $text );
	if ( false !== strpos( $t, 'ušetř' ) || false !== strpos( $t, 'zvýhod' ) || false !== strpos( $t, 'akce' ) ) {
		return 'is-zluta';
	}
	if ( false !== strpos( $t, 'obl' ) || false !== strpos( $t, 'hit' ) ) {
		return 'is-korala';
	}
	if ( false !== strpos( $t, 'sezón' ) || false !== strpos( $t, 'sezon' ) ) {
		return 'is-karamel';
	}
	if ( false !== strpos( $t, 'novin' ) ) {
		return 'is-mint';
	}
	return 'is-mlha';
}

add_action( 'add_meta_boxes_' . SKALICKA_MENU_PT, function () {
	add_meta_box( 'sk-menu-polozka', 'Cena a popis', 'skalicka_menu_metabox', SKALICKA_MENU_PT, 'normal', 'high' );
} );

function skalicka_menu_metabox( $post ) {
	wp_nonce_field( 'sk_menu_save', 'sk_menu_nonce' );
	$cena   = get_post_meta( $post->ID, 'sk_cena', true );
	$popis  = get_post_meta( $post->ID, 'sk_popis', true );
	$stitek = get_post_meta( $post->ID, 'sk_stitek', true );
	$tip    = get_post_meta( $post->ID, 'sk_tip', true );
	$ikona  = get_post_meta( $post->ID, 'sk_ikona', true );
	?>
	<style>.sk-mb label{display:block;font-weight:600;margin:12px 0 4px}.sk-mb .description{margin-top:4px}</style>
	<div class="sk-mb">
		<label for="sk-cena">Cena v Kč</label>
		<input id="sk-cena" name="sk_cena" type="text" class="regular-text" value="<?php echo esc_attr( $cena ); ?>" placeholder="např. 57, od 85 nebo 40 / 80">
		<p class="description">Jen číslo, „Kč“ se doplní samo. Jde napsat i „od 85“ nebo „40 / 80“.</p>

		<label for="sk-popis">Krátký popis</label>
		<textarea id="sk-popis" name="sk_popis" class="large-text" rows="2" placeholder="např. Espresso s napěněným mlékem"><?php echo esc_textarea( $popis ); ?></textarea>

		<label for="sk-stitek">Štítek</label>
		<input id="sk-stitek" name="sk_stitek" type="text" class="regular-text" list="sk-stitky" value="<?php echo esc_attr( $stitek ); ?>" placeholder="nic">
		<datalist id="sk-stitky"><?php foreach ( skalicka_menu_badges() as $b ) : ?><option value="<?php echo esc_attr( $b ); ?>"><?php endforeach; ?></datalist>
		<p class="description">Oblíbené, Novinka, Sezóna, Zvýhodněně nebo vlastní krátký text. Prázdné = bez štítku.</p>

		<label><input type="checkbox" name="sk_tip" value="1" <?php checked( '1', $tip ); ?>> Ukázat nahoře mezi tipy (velká karta s fotkou)</label>

		<label for="sk-ikona">Ikona, když položka nemá fotku</label>
		<select id="sk-ikona" name="sk_ikona">
			<option value="">Podle sekce</option>
			<?php foreach ( skalicka_menu_icons() as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $ikona, $k ); ?>><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>

		<p class="description" style="margin-top:16px">Sekci vyberte vpravo, pořadí v sekci v poli Atributy → Pořadí (menší číslo = výš). Fotku nahrajte vpravo dole, ideálně bez pozadí. <strong>Dočasně skrýt</strong> položku = uložit jako koncept.</p>
	</div>
	<?php
}

add_action( 'save_post_' . SKALICKA_MENU_PT, function ( $post_id ) {
	if ( ! isset( $_POST['sk_menu_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sk_menu_nonce'] ) ), 'sk_menu_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, 'sk_cena', skalicka_menu_clean_price( wp_unslash( $_POST['sk_cena'] ?? '' ) ) );
	update_post_meta( $post_id, 'sk_popis', sanitize_textarea_field( wp_unslash( $_POST['sk_popis'] ?? '' ) ) );
	update_post_meta( $post_id, 'sk_stitek', sanitize_text_field( wp_unslash( $_POST['sk_stitek'] ?? '' ) ) );
	update_post_meta( $post_id, 'sk_tip', empty( $_POST['sk_tip'] ) ? '' : '1' );
	$ikona = sanitize_key( wp_unslash( $_POST['sk_ikona'] ?? '' ) );
	update_post_meta( $post_id, 'sk_ikona', isset( skalicka_menu_icons()[ $ikona ] ) ? $ikona : '' );
} );

/** Cena: bez „Kč“ a „,-“, mezery srovnané. */
function skalicka_menu_clean_price( $v ) {
	$v = sanitize_text_field( (string) $v );
	$v = preg_replace( '/\s*(kč|,-)\s*$/iu', '', $v );
	$v = preg_replace( '/\s*\/\s*/', ' / ', $v );
	return trim( preg_replace( '/\s+/', ' ', $v ) );
}

/** První číslo z ceny (pro strukturovaná data). */
function skalicka_menu_price_number( $v ) {
	return preg_match( '/\d+(?:[.,]\d+)?/', (string) $v, $m ) ? str_replace( ',', '.', $m[0] ) : '';
}

/* ---------- Pole sekce ---------- */

function skalicka_menu_groups() {
	return array( 'piti' => 'Pití', 'jidlo' => 'Jídlo a sladké' );
}

function skalicka_menu_term_fields( $term = null ) {
	$id     = $term ? $term->term_id : 0;
	$poradi = $id ? get_term_meta( $id, 'sk_poradi', true ) : '';
	$sk     = $id ? get_term_meta( $id, 'sk_skupina', true ) : 'piti';
	$ik     = $id ? get_term_meta( $id, 'sk_ikona', true ) : '';
	$box    = $id ? get_term_meta( $id, 'sk_box', true ) : '';
	$rows   = array(
		'poradi'  => array( 'Pořadí', '<input type="number" name="sk_poradi" value="' . esc_attr( $poradi ) . '" class="small-text">', 'Menší číslo = výš na stránce.' ),
		'skupina' => array( 'Skupina', skalicka_menu_select( 'sk_skupina', skalicka_menu_groups(), $sk ), 'Pod jakým velkým nadpisem sekce je.' ),
		'ikona'   => array( 'Ikona', skalicka_menu_select( 'sk_ikona', skalicka_menu_icons(), $ik ), 'Pro položky bez fotky.' ),
		'box'     => array( 'Barva rámečku', skalicka_menu_select( 'sk_box', array( '' => 'Bílý', 'mint' => 'Mátový', 'zluta' => 'Žlutý', 'ruzova' => 'Růžový' ), $box ), '' ),
	);
	foreach ( $rows as $k => $r ) {
		if ( $term ) {
			echo '<tr class="form-field"><th scope="row">' . esc_html( $r[0] ) . '</th><td>' . $r[1] . ( $r[2] ? '<p class="description">' . esc_html( $r[2] ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo '<div class="form-field"><label>' . esc_html( $r[0] ) . '</label>' . $r[1] . ( $r[2] ? '<p>' . esc_html( $r[2] ) . '</p>' : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}

function skalicka_menu_select( $name, $options, $current ) {
	$h = '<select name="' . esc_attr( $name ) . '">';
	foreach ( $options as $k => $l ) {
		$h .= '<option value="' . esc_attr( $k ) . '"' . selected( (string) $current, (string) $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	return $h . '</select>';
}

add_action( SKALICKA_MENU_TAX . '_add_form_fields', function () {
	skalicka_menu_term_fields();
} );
add_action( SKALICKA_MENU_TAX . '_edit_form_fields', function ( $term ) {
	skalicka_menu_term_fields( $term );
} );

function skalicka_menu_save_term( $term_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['sk_skupina'] ) ) {
		return;
	}
	update_term_meta( $term_id, 'sk_poradi', (int) ( $_POST['sk_poradi'] ?? 0 ) );
	$sk = sanitize_key( wp_unslash( $_POST['sk_skupina'] ) );
	update_term_meta( $term_id, 'sk_skupina', isset( skalicka_menu_groups()[ $sk ] ) ? $sk : 'piti' );
	$ik = sanitize_key( wp_unslash( $_POST['sk_ikona'] ?? '' ) );
	update_term_meta( $term_id, 'sk_ikona', isset( skalicka_menu_icons()[ $ik ] ) ? $ik : 'kava' );
	$box = sanitize_key( wp_unslash( $_POST['sk_box'] ?? '' ) );
	update_term_meta( $term_id, 'sk_box', in_array( $box, array( 'mint', 'zluta', 'ruzova' ), true ) ? $box : '' );
}
add_action( 'created_' . SKALICKA_MENU_TAX, 'skalicka_menu_save_term' );
add_action( 'edited_' . SKALICKA_MENU_TAX, 'skalicka_menu_save_term' );

/* Popis sekce = poznámka pod nadpisem; v administraci ho přejmenovat. */
add_filter( 'manage_edit-' . SKALICKA_MENU_TAX . '_columns', function ( $cols ) {
	unset( $cols['slug'] );
	$cols['description'] = 'Poznámka pod nadpisem';
	$cols['sk_poradi']   = 'Pořadí';
	return $cols;
} );
add_filter( 'manage_' . SKALICKA_MENU_TAX . '_custom_column', function ( $out, $col, $term_id ) {
	return 'sk_poradi' === $col ? (string) (int) get_term_meta( $term_id, 'sk_poradi', true ) : $out;
}, 10, 3 );

/* ---------- Seznam položek v administraci ---------- */

add_filter( 'manage_' . SKALICKA_MENU_PT . '_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( 'title' === $k ) {
			$new['sk_foto'] = 'Fotka';
		}
		if ( 'date' === $k ) {
			continue;
		}
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['sk_cena']   = 'Cena';
			$new['sk_stitek'] = 'Štítek';
		}
	}
	$new['menu_order'] = 'Pořadí';
	return $new;
} );

add_action( 'manage_' . SKALICKA_MENU_PT . '_posts_custom_column', function ( $col, $id ) {
	if ( 'sk_foto' === $col ) {
		echo has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, array( 48, 48 ), array( 'style' => 'width:48px;height:48px;object-fit:contain;background:#FFF8F2;border-radius:50%' ) ) : '<span style="opacity:.4">—</span>';
	} elseif ( 'sk_cena' === $col ) {
		$c = get_post_meta( $id, 'sk_cena', true );
		echo $c ? '<strong>' . esc_html( $c ) . ' Kč</strong>' : '—';
	} elseif ( 'sk_stitek' === $col ) {
		echo esc_html( (string) get_post_meta( $id, 'sk_stitek', true ) ) . ( get_post_meta( $id, 'sk_tip', true ) ? ' <em>(tip)</em>' : '' );
	} elseif ( 'menu_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );

add_action( 'admin_head', function () {
	$screen = get_current_screen();
	if ( $screen && 'edit-' . SKALICKA_MENU_PT === $screen->id ) {
		echo '<style>.column-sk_foto{width:60px}.column-sk_cena{width:90px}.column-menu_order{width:70px}</style>';
	}
} );

/* V administraci řadit podle pořadí. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && SKALICKA_MENU_PT === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );

/* ---------- Rychlá úprava cen (jedna tabulka pro celé menu) ---------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=' . SKALICKA_MENU_PT, 'Rychlá úprava menu', 'Rychlá úprava cen', 'edit_pages', 'sk-menu-ceny', 'skalicka_menu_quick_page', 1 );
} );

/** Odkaz na stránku menu na webu (nebo null, když ještě neexistuje). */
function skalicka_menu_page() {
	return get_page_by_path( 'menu' );
}

function skalicka_menu_quick_page() {
	$data = skalicka_menu_data( array( 'publish', 'draft', 'pending', 'private' ) );
	$page = skalicka_menu_page();
	?>
	<div class="wrap">
		<h1>Rychlá úprava menu</h1>
		<?php if ( isset( $_GET['sk_ok'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Uloženo. Změny jsou hned vidět na webu.</p></div>
		<?php endif; ?>
		<p>Ceny, názvy a popisy všech položek na jednom místě. Odškrtnutím „Na webu“ položku dočasně skryjete (nic se nemaže).
		<?php if ( $page ) : ?><a href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener">Otevřít menu na webu</a><?php endif; ?></p>
		<style>
			.sk-q{max-width:1100px}.sk-q table{border-collapse:collapse;width:100%;background:#fff}.sk-q th,.sk-q td{padding:6px 8px;border-bottom:1px solid #e5e5e5;text-align:left;vertical-align:middle}
			.sk-q h2{margin:24px 0 8px}.sk-q input[type=text]{width:100%}.sk-q .c-cena{width:110px}.sk-q .c-web{width:60px;text-align:center}.sk-q .c-tip{width:50px;text-align:center}.sk-q .c-stitek{width:170px}
			.sk-q tr.is-off td{opacity:.55}.sk-q .c-foto img{width:36px;height:36px;object-fit:contain;border-radius:50%;background:#FFF8F2}.sk-q .c-foto{width:44px}
			.sk-q-save{position:sticky;bottom:0;background:#f0f0f1;padding:12px 0;margin-top:12px}
		</style>
		<form class="sk-q" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sk_menu_quick">
			<?php wp_nonce_field( 'sk_menu_quick' ); ?>
			<?php foreach ( $data as $sec ) : ?>
				<h2><?php echo esc_html( $sec['name'] ); ?></h2>
				<table>
					<thead><tr><th class="c-web">Na webu</th><th class="c-foto"></th><th>Název</th><th>Popis</th><th class="c-cena">Cena (Kč)</th><th class="c-stitek">Štítek</th><th class="c-tip">Tip</th><th></th></tr></thead>
					<tbody>
					<?php foreach ( $sec['items'] as $it ) : ?>
						<?php $n = 'p[' . (int) $it['id'] . ']'; ?>
						<tr class="<?php echo 'publish' === $it['status'] ? '' : 'is-off'; ?>">
							<td class="c-web"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[web]" value="1" <?php checked( 'publish' === $it['status'] ); ?> aria-label="Zobrazit <?php echo esc_attr( $it['name'] ); ?> na webu"></td>
							<td class="c-foto"><?php echo $it['photo'] ? wp_get_attachment_image( $it['photo'], 'thumbnail' ) : ''; ?></td>
							<td><input type="text" name="<?php echo esc_attr( $n ); ?>[nazev]" value="<?php echo esc_attr( $it['name'] ); ?>"></td>
							<td><input type="text" name="<?php echo esc_attr( $n ); ?>[popis]" value="<?php echo esc_attr( $it['desc'] ); ?>"></td>
							<td class="c-cena"><input type="text" name="<?php echo esc_attr( $n ); ?>[cena]" value="<?php echo esc_attr( $it['price'] ); ?>"></td>
							<td class="c-stitek"><input type="text" list="sk-stitky" name="<?php echo esc_attr( $n ); ?>[stitek]" value="<?php echo esc_attr( $it['badge'] ); ?>"></td>
							<td class="c-tip"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[tip]" value="1" <?php checked( $it['tip'] ); ?> aria-label="Tip nahoře"></td>
							<td><a href="<?php echo esc_url( get_edit_post_link( $it['id'] ) ); ?>">Upravit</a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
			<datalist id="sk-stitky"><?php foreach ( skalicka_menu_badges() as $b ) : ?><option value="<?php echo esc_attr( $b ); ?>"><?php endforeach; ?></datalist>
			<div class="sk-q-save"><?php submit_button( 'Uložit všechny změny', 'primary', 'submit', false ); ?></div>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_sk_menu_quick', function () {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'Nemáte oprávnění.' );
	}
	check_admin_referer( 'sk_menu_quick' );
	$rows = isset( $_POST['p'] ) && is_array( $_POST['p'] ) ? wp_unslash( $_POST['p'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- čistí se níže po polích
	foreach ( $rows as $id => $r ) {
		$id   = (int) $id;
		$post = get_post( $id );
		if ( ! $post || SKALICKA_MENU_PT !== $post->post_type || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		$title  = sanitize_text_field( $r['nazev'] ?? $post->post_title );
		$status = empty( $r['web'] ) ? 'draft' : 'publish';
		if ( $title !== $post->post_title || $status !== $post->post_status ) {
			wp_update_post( array( 'ID' => $id, 'post_title' => $title ? $title : $post->post_title, 'post_status' => $status ) );
		}
		update_post_meta( $id, 'sk_cena', skalicka_menu_clean_price( $r['cena'] ?? '' ) );
		update_post_meta( $id, 'sk_popis', sanitize_textarea_field( $r['popis'] ?? '' ) );
		update_post_meta( $id, 'sk_stitek', sanitize_text_field( $r['stitek'] ?? '' ) );
		update_post_meta( $id, 'sk_tip', empty( $r['tip'] ) ? '' : '1' );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=' . SKALICKA_MENU_PT . '&page=sk-menu-ceny&sk_ok=1' ) );
	exit;
} );

/* ---------- Data pro stránku ---------- */

/**
 * Sekce s položkami v pořadí. Vrací pole sekcí: id, slug, name, note, group, icon, box, items[].
 */
function skalicka_menu_data( $statuses = array( 'publish' ) ) {
	$terms = get_terms( array( 'taxonomy' => SKALICKA_MENU_TAX, 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}
	usort( $terms, function ( $a, $b ) {
		return (int) get_term_meta( $a->term_id, 'sk_poradi', true ) <=> (int) get_term_meta( $b->term_id, 'sk_poradi', true );
	} );
	$posts = get_posts( array(
		'post_type'      => SKALICKA_MENU_PT,
		'post_status'    => $statuses,
		'posts_per_page' => 300,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	) );
	$by_term = array();
	foreach ( $posts as $p ) {
		$t = wp_get_object_terms( $p->ID, SKALICKA_MENU_TAX, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $t ) || ! $t ) {
			continue;
		}
		$by_term[ (int) $t[0] ][] = $p;
	}
	$out = array();
	foreach ( $terms as $term ) {
		$icon  = (string) get_term_meta( $term->term_id, 'sk_ikona', true );
		$items = array();
		foreach ( $by_term[ $term->term_id ] ?? array() as $p ) {
			$own     = (string) get_post_meta( $p->ID, 'sk_ikona', true );
			$items[] = array(
				'id'     => $p->ID,
				'status' => $p->post_status,
				'name'   => $p->post_title,
				'price'  => (string) get_post_meta( $p->ID, 'sk_cena', true ),
				'desc'   => (string) get_post_meta( $p->ID, 'sk_popis', true ),
				'badge'  => (string) get_post_meta( $p->ID, 'sk_stitek', true ),
				'tip'    => '1' === get_post_meta( $p->ID, 'sk_tip', true ),
				'photo'  => (int) get_post_thumbnail_id( $p->ID ),
				'icon'   => $own ? $own : ( $icon ? $icon : 'kava' ),
			);
		}
		if ( ! $items && $statuses === array( 'publish' ) ) {
			continue;
		}
		$group = (string) get_term_meta( $term->term_id, 'sk_skupina', true );
		$out[] = array(
			'id'    => $term->term_id,
			'slug'  => $term->slug,
			'name'  => $term->name,
			'note'  => $term->description,
			'group' => isset( skalicka_menu_groups()[ $group ] ) ? $group : 'piti',
			'icon'  => $icon ? $icon : 'kava',
			'box'   => (string) get_term_meta( $term->term_id, 'sk_box', true ),
			'items' => $items,
		);
	}
	return $out;
}

/** Cena pro zobrazení: „57 Kč“, „od 85 Kč“, „40 / 80 Kč“. */
function skalicka_menu_price_html( $price ) {
	$price = trim( (string) $price );
	if ( '' === $price ) {
		return '';
	}
	$h = esc_html( $price );
	$h = preg_replace( '/^od\s+/u', '<small>od</small> ', $h );
	return $h . '<small> Kč</small>';
}

/** Strukturovaná data pro Google: menu kavárny se sekcemi a cenami. */
function skalicka_menu_schema( $data ) {
	$sections = array();
	foreach ( $data as $sec ) {
		$items = array();
		foreach ( $sec['items'] as $it ) {
			$item = array( '@type' => 'MenuItem', 'name' => $it['name'] );
			if ( $it['desc'] ) {
				$item['description'] = $it['desc'];
			}
			$num = skalicka_menu_price_number( $it['price'] );
			if ( '' !== $num ) {
				$item['offers'] = array( '@type' => 'Offer', 'price' => $num, 'priceCurrency' => 'CZK' );
			}
			if ( $it['photo'] ) {
				$src = wp_get_attachment_image_url( $it['photo'], 'medium_large' );
				if ( $src ) {
					$item['image'] = $src;
				}
			}
			$items[] = $item;
		}
		$sections[] = array( '@type' => 'MenuSection', 'name' => $sec['name'], 'hasMenuItem' => $items );
	}
	return array(
		'@context'       => 'https://schema.org',
		'@type'          => 'Menu',
		'name'           => 'Menu kavárny – ' . get_bloginfo( 'name' ),
		'url'            => skalicka_page_url( 'menu' ),
		'inLanguage'     => 'cs',
		'hasMenuSection' => $sections,
	);
}

/* ---------- Načtení menu najednou (jen pro správce, z prohlížeče přes REST s nonce) ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'skalicka/v1', '/menu-import', array(
		'methods'             => 'POST',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'callback'            => 'skalicka_menu_import',
	) );
} );

/**
 * Tělo: { sekce: [{slug, nazev, poradi, skupina, ikona, box, poznamka}],
 *         polozky: [{klic, nazev, sekce, cena, popis, stitek, tip, poradi, foto, ikona, skryt}] }
 * Položky se párují podle „klic“ (meta sk_klic), takže opakované načtení jen aktualizuje.
 */
function skalicka_menu_import( WP_REST_Request $req ) {
	$body  = $req->get_json_params();
	$terms = array();
	foreach ( (array) ( $body['sekce'] ?? array() ) as $s ) {
		$slug = sanitize_title( $s['slug'] ?? '' );
		$name = sanitize_text_field( $s['nazev'] ?? '' );
		if ( ! $slug || ! $name ) {
			continue;
		}
		$t = get_term_by( 'slug', $slug, SKALICKA_MENU_TAX );
		if ( $t ) {
			wp_update_term( $t->term_id, SKALICKA_MENU_TAX, array( 'name' => $name, 'description' => sanitize_text_field( $s['poznamka'] ?? '' ) ) );
			$tid = $t->term_id;
		} else {
			$r = wp_insert_term( $name, SKALICKA_MENU_TAX, array( 'slug' => $slug, 'description' => sanitize_text_field( $s['poznamka'] ?? '' ) ) );
			if ( is_wp_error( $r ) ) {
				return $r;
			}
			$tid = (int) $r['term_id'];
		}
		update_term_meta( $tid, 'sk_poradi', (int) ( $s['poradi'] ?? 0 ) );
		update_term_meta( $tid, 'sk_skupina', isset( skalicka_menu_groups()[ $s['skupina'] ?? '' ] ) ? $s['skupina'] : 'piti' );
		update_term_meta( $tid, 'sk_ikona', isset( skalicka_menu_icons()[ $s['ikona'] ?? '' ] ) ? $s['ikona'] : 'kava' );
		update_term_meta( $tid, 'sk_box', in_array( $s['box'] ?? '', array( 'mint', 'zluta', 'ruzova' ), true ) ? $s['box'] : '' );
		$terms[ $slug ] = $tid;
	}
	$done = array();
	foreach ( (array) ( $body['polozky'] ?? array() ) as $p ) {
		$key  = sanitize_key( $p['klic'] ?? '' );
		$slug = sanitize_title( $p['sekce'] ?? '' );
		if ( ! $key || ! $slug ) {
			continue;
		}
		if ( ! isset( $terms[ $slug ] ) ) {
			$t = get_term_by( 'slug', $slug, SKALICKA_MENU_TAX );
			if ( ! $t ) {
				continue;
			}
			$terms[ $slug ] = $t->term_id;
		}
		$found = get_posts( array( 'post_type' => SKALICKA_MENU_PT, 'post_status' => 'any', 'meta_key' => 'sk_klic', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1 ) );
		$args  = array(
			'post_type'   => SKALICKA_MENU_PT,
			'post_title'  => sanitize_text_field( $p['nazev'] ?? $key ),
			'post_status' => empty( $p['skryt'] ) ? 'publish' : 'draft',
			'menu_order'  => (int) ( $p['poradi'] ?? 0 ),
		);
		if ( $found ) {
			$args['ID'] = (int) $found[0];
			$id         = wp_update_post( $args, true );
		} else {
			$id = wp_insert_post( $args, true );
		}
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $id, 'sk_klic', $key );
		update_post_meta( $id, 'sk_cena', skalicka_menu_clean_price( $p['cena'] ?? '' ) );
		update_post_meta( $id, 'sk_popis', sanitize_textarea_field( $p['popis'] ?? '' ) );
		update_post_meta( $id, 'sk_stitek', sanitize_text_field( $p['stitek'] ?? '' ) );
		update_post_meta( $id, 'sk_tip', empty( $p['tip'] ) ? '' : '1' );
		update_post_meta( $id, 'sk_ikona', isset( skalicka_menu_icons()[ $p['ikona'] ?? '' ] ) ? $p['ikona'] : '' );
		wp_set_object_terms( $id, array( (int) $terms[ $slug ] ), SKALICKA_MENU_TAX );
		if ( isset( $p['foto'] ) ) {
			$foto = (int) $p['foto'];
			if ( $foto && wp_attachment_is_image( $foto ) ) {
				set_post_thumbnail( $id, $foto );
			} elseif ( ! $foto ) {
				delete_post_thumbnail( $id );
			}
		}
		$done[ $key ] = $id;
	}
	return array( 'sekce' => $terms, 'polozky' => $done );
}

/* ---------- Stránka menu: SEO, schéma, skripty ---------- */

function skalicka_is_menu_page() {
	return is_page( 'menu' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( skalicka_is_menu_page() ) {
		wp_enqueue_script( 'skalicka-menu', get_template_directory_uri() . '/assets/js/menu.js', array(), SKALICKA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
} );

add_action( 'wp_head', function () {
	if ( skalicka_is_menu_page() ) {
		$data = skalicka_menu_data();
		if ( $data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( skalicka_menu_schema( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	}
}, 4 );

/* Soukromá stránka nemá v titulku „Soukromé:“ – na webu ji stejně vidí jen přihlášený správce. */
add_filter( 'private_title_format', function ( $format, $post ) {
	return ( $post && 'menu' === $post->post_name ) ? '%s' : $format;
}, 10, 2 );

/**
 * [skalicka_menu_odkaz] – tlačítko na celé menu. Dokud stránka Menu není zveřejněná,
 * zobrazí náhradní text (atribut „jinak“), aby návštěvníci nenarazili na prázdnou stránku.
 */
add_shortcode( 'skalicka_menu_odkaz', function ( $atts ) {
	$atts = shortcode_atts( array( 'text' => 'Celé menu s cenami a fotkami', 'jinak' => '' ), $atts );
	$page = skalicka_menu_page();
	if ( ! $page || 'publish' !== $page->post_status ) {
		return $atts['jinak'] ? '<p>' . esc_html( $atts['jinak'] ) . '</p>' : '';
	}
	return '<p><a class="sk-btn sk-btn--zluta" href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $atts['text'] ) . '</a></p>';
} );
add_filter( 'no_texturize_shortcodes', function ( $list ) {
	$list[] = 'skalicka_menu_odkaz';
	return $list;
} );
