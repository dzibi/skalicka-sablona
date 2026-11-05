<?php
/**
 * Jednoduchý formulář „Přidat do kalendáře“ (i pro mobil) a zjednodušený editor akcí.
 * Vytváří akce The Events Calendar (tribe_events) s typem = kategorií kalendáře.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Typy, které umí web barevně odlišit: slug kategorie => [název, popis, barva]. */
function skalicka_rychla_typy() {
	return array(
		'mimoradne' => array( 'Mimořádná událost', 'oranžová, máme otevřeno (např. zavřené garáže)', '#FDB06B' ),
		'zavreno'   => array( 'Zavřeno', 'červená, herna je zavřená (celý den nebo část dne)', '#E84F37' ),
		''          => array( 'Akce', 'zelená, běžná akce v herně', '#9BD58A' ),
		'pohybovky' => array( 'Pohybovky', 'lekce s rezervací', '#9BD58A' ),
	);
}

function skalicka_rychla_cap() {
	return 'publish_posts';
}

add_action( 'admin_menu', function () {
	if ( ! post_type_exists( 'tribe_events' ) ) {
		return;
	}
	add_menu_page( 'Přidat do kalendáře', 'Do kalendáře', skalicka_rychla_cap(), 'skalicka-kalendar', 'skalicka_rychla_page', 'dashicons-calendar-alt', 2 );
} );

/* Odkaz „+ Do kalendáře“ v horní liště (na mobilu nejrychlejší cesta). */
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! current_user_can( skalicka_rychla_cap() ) || ! post_type_exists( 'tribe_events' ) ) {
		return;
	}
	$bar->add_node( array(
		'id'    => 'skalicka-kalendar',
		'title' => '<span class="ab-icon dashicons dashicons-calendar-alt" style="top:2px"></span><span class="ab-label">Do kalendáře</span>',
		'href'  => admin_url( 'admin.php?page=skalicka-kalendar' ),
	) );
}, 60 );

/** Vytvoří akci. Vrací ID nebo WP_Error. */
function skalicka_rychla_create( array $d ) {
	$tz      = wp_timezone();
	$allday  = ! empty( $d['allday'] );
	$from_d  = $d['od'];
	$to_d    = $d['do'] ? $d['do'] : $d['od'];
	$from_t  = $allday ? '00:00' : $d['cas_od'];
	$to_t    = $allday ? '23:59' : $d['cas_do'];
	$start   = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $from_d . ' ' . $from_t, $tz );
	$end     = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $to_d . ' ' . $to_t, $tz );
	if ( ! $start || ! $end ) {
		return new WP_Error( 'datum', 'Zkontrolujte datum a čas.' );
	}
	if ( $end <= $start ) {
		return new WP_Error( 'datum', 'Konec musí být po začátku.' );
	}
	$args = array(
		'post_title'       => $d['nazev'],
		'post_content'     => $d['popis'],
		'post_status'      => 'publish',
		'EventStartDate'   => $start->format( 'Y-m-d' ),
		'EventEndDate'     => $end->format( 'Y-m-d' ),
		'EventStartHour'   => $start->format( 'H' ),
		'EventStartMinute' => $start->format( 'i' ),
		'EventEndHour'     => $end->format( 'H' ),
		'EventEndMinute'   => $end->format( 'i' ),
		'EventTimezone'    => $tz->getName(),
	);
	if ( $allday ) {
		$args['EventAllDay'] = 'yes';
	}
	if ( ! function_exists( 'tribe_create_event' ) ) {
		return new WP_Error( 'tec', 'Chybí plugin kalendáře.' );
	}
	$id = tribe_create_event( $args );
	if ( ! $id || is_wp_error( $id ) ) {
		return is_wp_error( $id ) ? $id : new WP_Error( 'tec', 'Akci se nepodařilo uložit.' );
	}
	if ( $d['typ'] ) {
		$term = get_term_by( 'slug', $d['typ'], 'tribe_events_cat' );
		if ( $term ) {
			wp_set_object_terms( $id, array( (int) $term->term_id ), 'tribe_events_cat' );
		}
	}
	return (int) $id;
}

add_action( 'admin_post_skalicka_rychla_akce', function () {
	if ( ! current_user_can( skalicka_rychla_cap() ) ) {
		wp_die( 'Nemáte oprávnění.' );
	}
	check_admin_referer( 'skalicka_rychla_akce' );
	$typy = skalicka_rychla_typy();
	$d    = array(
		'typ'    => isset( $_POST['typ'] ) && isset( $typy[ wp_unslash( $_POST['typ'] ) ] ) ? sanitize_key( wp_unslash( $_POST['typ'] ) ) : 'mimoradne',
		'nazev'  => sanitize_text_field( wp_unslash( $_POST['nazev'] ?? '' ) ),
		'od'     => sanitize_text_field( wp_unslash( $_POST['od'] ?? '' ) ),
		'do'     => sanitize_text_field( wp_unslash( $_POST['do'] ?? '' ) ),
		'allday' => ! empty( $_POST['allday'] ),
		'cas_od' => sanitize_text_field( wp_unslash( $_POST['cas_od'] ?? '' ) ),
		'cas_do' => sanitize_text_field( wp_unslash( $_POST['cas_do'] ?? '' ) ),
		'popis'  => wp_kses_post( wp_unslash( $_POST['popis'] ?? '' ) ),
	);
	$back = admin_url( 'admin.php?page=skalicka-kalendar' );
	if ( '' === $d['nazev'] || '' === $d['od'] ) {
		wp_safe_redirect( add_query_arg( 'sk_err', rawurlencode( 'Vyplňte název a datum.' ), $back ) );
		exit;
	}
	$id = skalicka_rychla_create( $d );
	if ( is_wp_error( $id ) ) {
		wp_safe_redirect( add_query_arg( 'sk_err', rawurlencode( $id->get_error_message() ), $back ) );
		exit;
	}
	wp_safe_redirect( add_query_arg( 'sk_ok', $id, $back ) );
	exit;
} );

function skalicka_rychla_page() {
	$tz    = wp_timezone();
	$today = new DateTimeImmutable( 'today', $tz );
	$typy  = skalicka_rychla_typy();
	?>
	<style>
	.skr{max-width:640px;font-size:16px}
	.skr h1{font-size:26px;margin:10px 0 18px}
	.skr .card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px;margin-bottom:18px}
	.skr label.b{display:block;font-weight:600;margin:14px 0 6px}
	.skr input[type=text],.skr input[type=date],.skr input[type=time],.skr textarea{width:100%;font-size:17px;padding:10px 12px;border-radius:8px;min-height:46px}
	.skr textarea{min-height:110px}
	.skr .row{display:flex;gap:12px}.skr .row>div{flex:1}
	.skr .typ{display:grid;gap:8px}
	.skr .typ label{display:flex;gap:10px;align-items:flex-start;border:2px solid #dcdcde;border-radius:10px;padding:10px 12px;cursor:pointer}
	.skr .typ input{margin-top:3px}
	.skr .typ i{flex:none;width:18px;height:18px;border-radius:5px;margin-top:2px}
	.skr .typ small{display:block;color:#646970}
	.skr .typ label:has(input:checked){border-color:#3C3231;background:#FFF8F2}
	.skr .allday{display:flex;gap:10px;align-items:center;margin-top:16px;font-weight:600}
	.skr .allday input{width:22px;height:22px}
	.skr .casy[hidden]{display:none}
	.skr .btn{display:block;width:100%;font-size:18px;padding:14px;border-radius:10px;margin-top:20px;background:#3C3231;border:0;color:#fff;font-weight:700;cursor:pointer}
	.skr .ok{border-left:4px solid #2E8B57}.skr .err{border-left:4px solid #E84F37}
	.skr ul.list{margin:0}.skr ul.list li{display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid #f0f0f1}
	.skr ul.list li i{flex:none;width:12px;height:12px;border-radius:3px}
	.skr ul.list li span{flex:1}.skr ul.list li a{white-space:nowrap}
	</style>
	<div class="wrap skr">
		<h1>Přidat do kalendáře</h1>
		<?php if ( isset( $_GET['sk_ok'] ) ) : $id = (int) $_GET['sk_ok']; ?>
			<div class="card ok"><strong>Hotovo, akce je na webu.</strong><br><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Zobrazit úvodní stránku</a> · <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Upravit akci</a></div>
		<?php elseif ( isset( $_GET['sk_err'] ) ) : ?>
			<div class="card err"><strong>Neuloženo:</strong> <?php echo esc_html( wp_unslash( $_GET['sk_err'] ) ); ?></div>
		<?php endif; ?>
		<form class="card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="skalicka_rychla_akce">
			<?php wp_nonce_field( 'skalicka_rychla_akce' ); ?>
			<label class="b">Co to je</label>
			<div class="typ">
				<?php $first = true; foreach ( $typy as $slug => $t ) : ?>
					<label><input type="radio" name="typ" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $first ); ?>><i style="background:<?php echo esc_attr( $t[2] ); ?>"></i><span><strong><?php echo esc_html( $t[0] ); ?></strong><small><?php echo esc_html( $t[1] ); ?></small></span></label>
				<?php $first = false; endforeach; ?>
			</div>
			<label class="b" for="skr-nazev">Název (krátce, ukáže se na webu)</label>
			<input type="text" id="skr-nazev" name="nazev" required placeholder="např. Garáže OC zavřené, parkujte v okolí">
			<div class="row">
				<div><label class="b" for="skr-od">Od</label><input type="date" id="skr-od" name="od" required value="<?php echo esc_attr( $today->format( 'Y-m-d' ) ); ?>"></div>
				<div><label class="b" for="skr-do">Do</label><input type="date" id="skr-do" name="do" value="<?php echo esc_attr( $today->format( 'Y-m-d' ) ); ?>"></div>
			</div>
			<label class="allday"><input type="checkbox" name="allday" id="skr-allday" value="1" checked> Celý den</label>
			<div class="row casy" id="skr-casy" hidden>
				<div><label class="b" for="skr-cod">Čas od</label><input type="time" id="skr-cod" name="cas_od" value="09:00"></div>
				<div><label class="b" for="skr-cdo">Čas do</label><input type="time" id="skr-cdo" name="cas_do" value="18:30"></div>
			</div>
			<label class="b" for="skr-popis">Popis (detail akce, nepovinné)</label>
			<textarea id="skr-popis" name="popis" placeholder="Víc informací pro rodiče"></textarea>
			<button class="btn" type="submit">Přidat do kalendáře</button>
		</form>
		<script>
		(function(){
			var a=document.getElementById('skr-allday'),c=document.getElementById('skr-casy'),od=document.getElementById('skr-od'),dd=document.getElementById('skr-do');
			function t(){c.hidden=a.checked;}a.addEventListener('change',t);t();
			od.addEventListener('change',function(){if(!dd.value||dd.value<od.value){dd.value=od.value;}});
		})();
		</script>
		<div class="card">
			<strong>Na webu v příštích 14 dnech</strong>
			<ul class="list">
			<?php
			$list = function_exists( 'skalicka_events_between' ) ? skalicka_events_between( $today, $today->modify( '+14 days' ), null, 40 ) : array();
			if ( ! $list ) {
				echo '<li><span>Nic naplánovaného.</span></li>';
			}
			foreach ( $list as $e ) {
				$col = '#9BD58A';
				foreach ( $typy as $slug => $t ) {
					if ( $slug && in_array( $slug, $e['cats'], true ) ) {
						$col = $t[2];
					}
				}
				$when = $e['start']->format( 'j. n.' ) . ( $e['allday'] ? '' : ' ' . $e['start']->format( 'G:i' ) );
				echo '<li><i style="background:' . esc_attr( $col ) . '"></i><span><b>' . esc_html( $when ) . '</b> ' . esc_html( $e['title'] ) . '</span>'
					. '<a href="' . esc_url( get_edit_post_link( $e['id'] ) ) . '">Upravit</a> · <a href="' . esc_url( get_delete_post_link( $e['id'] ) ) . '" onclick="return confirm(\'Smazat akci z webu?\')">Smazat</a></li>';
			}
			?>
			</ul>
		</div>
	</div>
	<?php
}

/* Zjednodušený editor akce: pryč s boxy, které herna nepoužívá. */
add_action( 'add_meta_boxes_tribe_events', function () {
	foreach ( array( 'tagsdiv-post_tag', 'tribe_events_event_options', 'tribe-events-status', 'postexcerpt', 'postcustom', 'authordiv', 'slugdiv' ) as $box ) {
		remove_meta_box( $box, 'tribe_events', 'side' );
		remove_meta_box( $box, 'tribe_events', 'normal' );
		remove_meta_box( $box, 'tribe_events', 'advanced' );
	}
}, 99 );
