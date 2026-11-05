</main>
<footer class="sk-footer">
	<div class="sk-wrap">
		<div class="sk-footer-grid">
			<div>
				<h2><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
				<p><?php echo nl2br( esc_html( skalicka_opt( 'address' ) ) ); ?></p>
				<p><a href="<?php echo esc_url( skalicka_link( 'navigace' ) ); ?>" rel="noopener">Navigovat k nám</a></p>
			</div>
			<div>
				<h2>Otevírací doba</h2>
				<p><?php echo esc_html( skalicka_hours_short() ); ?></p>
				<p><span class="sk-semafor-text">&nbsp;</span></p>
				<p><a href="<?php echo esc_url( skalicka_calendar_url() ); ?>">Svátky a akce v kalendáři</a></p>
			</div>
			<div>
				<h2>Kontakt</h2>
				<ul>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'kontakt' ) . '#napiste-nam' ); ?>">Napište nám</a></li>
					<?php if ( skalicka_opt( 'phone' ) ) : ?>
						<li><a href="<?php echo esc_url( skalicka_link( 'telefon' ) ); ?>"><?php echo esc_html( skalicka_opt( 'phone' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( skalicka_opt( 'email' ) ) : ?>
						<li><a href="<?php echo esc_url( skalicka_link( 'email' ) ); ?>"><?php echo esc_html( skalicka_opt( 'email' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( skalicka_opt( 'ig_url' ) ) : ?>
						<li><a href="<?php echo esc_url( skalicka_opt( 'ig_url' ) ); ?>" rel="noopener">Instagram</a></li>
					<?php endif; ?>
					<?php if ( skalicka_opt( 'fb_url' ) ) : ?>
						<li><a href="<?php echo esc_url( skalicka_opt( 'fb_url' ) ); ?>" rel="noopener">Facebook</a></li>
					<?php endif; ?>
				</ul>
			</div>
			<div>
				<h2>Pro členy a rodiče</h2>
				<ul>
					<?php if ( skalicka_opt( 'url_portal' ) ) : ?>
						<li><a href="<?php echo esc_url( skalicka_opt( 'url_portal' ) ); ?>" rel="noopener">Spravovat členství</a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'navsteva' ) . '#caste-otazky' ); ?>">Časté otázky</a></li>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'skolky-a-skoly' ) ); ?>">Školky a školy</a></li>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'kontakt' ) ); ?>">Kontakt</a></li>
				</ul>
			</div>
			<div>
				<h2>Dokumenty</h2>
				<ul>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'zasady-ochrany-osobnich-udaju' ) ); ?>">Zásady ochrany osobních údajů</a></li>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'podminky-clenstvi' ) ); ?>">Podmínky členství</a></li>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'provozni-rad' ) ); ?>">Provozní řád</a></li>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'reklamacni-rad' ) ); ?>">Reklamační řád</a></li>
					<li><a href="<?php echo esc_url( skalicka_page_url( 'cookies' ) ); ?>">Cookies</a></li>
					<li><a href="#" data-sk-cookies>Nastavení cookies</a></li>
				</ul>
			</div>
		</div>
		<?php
		$kzn = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'title' => 'Kudy z nudy logo', 'numberposts' => 1, 'fields' => 'ids' ) );
		?>
		<div class="sk-partner">
			<a href="https://www.kudyznudy.cz/" rel="noopener" target="_blank">
				<?php if ( $kzn ) : ?>
					<img src="<?php echo esc_url( wp_get_attachment_url( $kzn[0] ) ); ?>" alt="Kudy z nudy" width="246" height="56" loading="lazy">
				<?php else : ?>
					Kudy z nudy
				<?php endif; ?>
			</a>
			<span>Tipy na výlety s dětmi najdete na Kudy z nudy.</span>
		</div>
		<div class="sk-footer-bottom">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Dětský svět Skalička</div>
	</div>
</footer>
<nav class="sk-mbar" aria-label="Rychlé akce">
	<a href="<?php echo esc_url( skalicka_link( 'navigace' ) ); ?>" rel="noopener">Navigovat</a>
	<?php if ( skalicka_link( 'telefon' ) ) : ?>
		<a href="<?php echo esc_url( skalicka_link( 'telefon' ) ); ?>">Zavolat</a>
	<?php else : ?>
		<a href="<?php echo esc_url( skalicka_page_url( 'kontakt' ) ); ?>">Kontakt</a>
	<?php endif; ?>
	<details>
		<summary>Rezervovat</summary>
		<?php skalicka_reservation_menu(); ?>
	</details>
</nav>
<?php wp_footer(); ?>
</body>
</html>
