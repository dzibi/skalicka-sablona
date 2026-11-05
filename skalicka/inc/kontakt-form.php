<?php
/**
 * Kontaktní formulář [skalicka_formular] – zprávy odchází e-mailem na info@ (nastavení Skalička → e-mail).
 * Nic se neukládá do databáze. Ochrana proti spamu: skryté pole, kontrola JavaScriptu a limit na IP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_form_temata() {
	return array( 'Obecný dotaz', 'Oslava', 'Členství', 'Pohybovky', 'Školky a školy', 'Jiné' );
}

function skalicka_form_prijemce() {
	$m = skalicka_opt( 'email' );
	return is_email( $m ) ? $m : 'info@svetskalicka.cz';
}

add_shortcode( 'skalicka_formular', function () {
	$stav = isset( $_GET['zprava'] ) ? sanitize_key( wp_unslash( $_GET['zprava'] ) ) : '';
	ob_start();
	?>
	<section class="sk-form-wrap" id="napiste-nam">
		<h2>Napište nám</h2>
		<p>Máte dotaz k herně, oslavě nebo členství? Napište nám a ozveme se vám co nejdřív.</p>
		<?php if ( 'ok' === $stav ) : ?>
			<p class="sk-form-ok" role="status">Děkujeme, zprávu máme. Ozveme se vám na e-mail, který jste uvedli.</p>
		<?php else : ?>
			<?php if ( 'chyba' === $stav ) : ?>
				<p class="sk-form-err" role="alert">Zprávu se nepodařilo odeslat. Zkontrolujte prosím jméno, e-mail a zprávu, nebo nám napište přímo na <a href="mailto:<?php echo esc_attr( skalicka_form_prijemce() ); ?>"><?php echo esc_html( skalicka_form_prijemce() ); ?></a>.</p>
			<?php endif; ?>
			<form class="sk-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="skalicka_kontakt">
				<input type="hidden" name="sk_js" value="">
				<div class="sk-form-hp" aria-hidden="true"><label>Web <input type="text" name="sk_web" tabindex="-1" autocomplete="off"></label></div>
				<div class="sk-form-row">
					<p><label for="skf-jmeno">Jméno *</label><input id="skf-jmeno" type="text" name="jmeno" required autocomplete="name" maxlength="120"></p>
					<p><label for="skf-email">E-mail *</label><input id="skf-email" type="email" name="email" required autocomplete="email" maxlength="160"></p>
				</div>
				<div class="sk-form-row">
					<p><label for="skf-tel">Telefon <small>(nepovinné)</small></label><input id="skf-tel" type="tel" name="telefon" autocomplete="tel" maxlength="40"></p>
					<p><label for="skf-tema">O co jde?</label><select id="skf-tema" name="tema">
						<?php foreach ( skalicka_form_temata() as $t ) : ?><option><?php echo esc_html( $t ); ?></option><?php endforeach; ?>
					</select></p>
				</div>
				<p><label for="skf-zprava">Zpráva *</label><textarea id="skf-zprava" name="zprava" required rows="6" maxlength="5000"></textarea></p>
				<p><button type="submit" class="sk-btn">Odeslat zprávu</button></p>
				<p class="sk-form-note">Údaje použijeme jen k odpovědi na vaši zprávu. Víc v <a href="<?php echo esc_url( skalicka_page_url( 'zasady-ochrany-osobnich-udaju' ) ); ?>">zásadách ochrany osobních údajů</a>.</p>
			</form>
			<script>
			(function(){var f=document.querySelector('.sk-form');if(!f)return;var j=f.querySelector('[name=sk_js]');
			function ok(){j.value='sk-'+(new Date()).getFullYear();}
			f.addEventListener('focusin',ok,{once:true});f.addEventListener('submit',ok);})();
			</script>
		<?php endif; ?>
	</section>
	<?php
	return ob_get_clean();
} );

add_filter( 'no_texturize_shortcodes', function ( $list ) {
	$list[] = 'skalicka_formular';
	return $list;
} );

function skalicka_form_odeslat() {
	$back = wp_get_referer();
	if ( ! $back || 0 !== strpos( $back, home_url() ) ) {
		$back = skalicka_page_url( 'kontakt' );
	}
	$back = remove_query_arg( 'zprava', strtok( $back, '#' ) );
	$fail = add_query_arg( 'zprava', 'chyba', $back ) . '#napiste-nam';

	$jmeno  = sanitize_text_field( wp_unslash( $_POST['jmeno'] ?? '' ) );
	$email  = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$tel    = sanitize_text_field( wp_unslash( $_POST['telefon'] ?? '' ) );
	$tema   = sanitize_text_field( wp_unslash( $_POST['tema'] ?? '' ) );
	$zprava = sanitize_textarea_field( wp_unslash( $_POST['zprava'] ?? '' ) );
	$hp     = (string) ( $_POST['sk_web'] ?? '' );
	$js     = (string) ( $_POST['sk_js'] ?? '' );

	// Robot: vyplnil skryté pole nebo neběžel JavaScript – tváříme se, že je hotovo.
	if ( '' !== $hp || 0 !== strpos( $js, 'sk-' ) ) {
		wp_safe_redirect( add_query_arg( 'zprava', 'ok', $back ) . '#napiste-nam' );
		exit;
	}
	if ( '' === $jmeno || ! is_email( $email ) || '' === $zprava ) {
		wp_safe_redirect( $fail );
		exit;
	}
	if ( ! in_array( $tema, skalicka_form_temata(), true ) ) {
		$tema = 'Obecný dotaz';
	}
	// Limit 5 zpráv za hodinu z jedné adresy.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'sk_form_' . md5( $ip );
	$cnt = (int) get_transient( $key );
	if ( $cnt >= 5 ) {
		wp_safe_redirect( $fail );
		exit;
	}
	set_transient( $key, $cnt + 1, HOUR_IN_SECONDS );

	$to      = skalicka_form_prijemce();
	$domain  = wp_parse_url( home_url(), PHP_URL_HOST );
	$subject = 'Web: ' . $tema . ' – ' . $jmeno;
	$body    = "Nová zpráva z kontaktního formuláře na webu.\n\n"
		. 'Jméno: ' . $jmeno . "\n"
		. 'E-mail: ' . $email . "\n"
		. 'Telefon: ' . ( $tel ? $tel : '–' ) . "\n"
		. 'Téma: ' . $tema . "\n\n"
		. $zprava . "\n\n"
		. "––\nOdpovězte přímo na tento e-mail, odpověď půjde odesílateli.";
	$headers = array(
		'From: Web Dětský svět Skalička <web@' . $domain . '>',
		'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $jmeno ) . ' <' . $email . '>',
		'Content-Type: text/plain; charset=UTF-8',
	);
	$sent = wp_mail( $to, $subject, $body, $headers );
	wp_safe_redirect( add_query_arg( 'zprava', $sent ? 'ok' : 'chyba', $back ) . '#napiste-nam' );
	exit;
}
add_action( 'admin_post_nopriv_skalicka_kontakt', 'skalicka_form_odeslat' );
add_action( 'admin_post_skalicka_kontakt', 'skalicka_form_odeslat' );
