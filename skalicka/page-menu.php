<?php
/**
 * Stránka Menu kavárny (adresa /menu/, sem vede QR kód z menu na stolech).
 * Položky, ceny a fotky se spravují v administraci: Menu kavárny (rychle v „Rychlá úprava cen“).
 */

get_header();

$data   = skalicka_menu_data();
$groups = skalicka_menu_groups();
$tips   = array();
foreach ( $data as $sec ) {
	foreach ( $sec['items'] as $it ) {
		if ( $it['tip'] ) {
			$tips[] = $it;
		}
	}
}
$clenstvi = skalicka_page_url( 'clenstvi' );
$oslavy   = skalicka_page_url( 'oslavy' );

/** Fotka nebo ikona položky. */
$sk_thumb = function ( $it, $size, $sizes, $eager = false ) {
	if ( $it['photo'] ) {
		$img = wp_get_attachment_image( $it['photo'], $size, false, array(
			'alt'      => '',
			'loading'  => $eager ? 'eager' : 'lazy',
			'decoding' => 'async',
			'sizes'    => $sizes,
		) );
		if ( $img ) {
			return $img;
		}
	}
	return skalicka_menu_icon_svg( $it['icon'] );
};
?>
<div class="sk-pagehead sk-mhead">
	<div class="sk-wrap">
		<h1>Menu</h1>
		<p class="sk-mhead-lead">Káva, domácí limonády, vafle a&nbsp;pizza. Vy si v&nbsp;klidu sednete a&nbsp;děti máte na očích.</p>
		<ul class="sk-mhead-pills">
			<li class="is-zluta"><a href="<?php echo esc_url( $clenstvi ); ?>">Členové mají 10&nbsp;% z&nbsp;účtu</a></li>
			<li>Jídlo vám zabalíme i&nbsp;s&nbsp;sebou</li>
		</ul>
		<?php echo skalicka_page_art( 'kavarna-a-menu' ); // phpcs:ignore WordPress.Security.EscapeOutput -- pevné SVG ?>
	</div>
	<?php skalicka_wave(); ?>
</div>

<?php if ( ! $data ) : ?>
	<div class="sk-page"><p>Menu právě připravujeme. Celou nabídku najdete na baru v herně.</p></div>
<?php else : ?>

<nav class="sk-mnav" id="sk-mnav" aria-label="Sekce menu">
	<div class="sk-mnav-in">
		<?php if ( $tips ) : ?><a href="#tipy">Tipy</a><?php endif; ?>
		<?php foreach ( $data as $sec ) : ?>
			<a href="#<?php echo esc_attr( $sec['slug'] ); ?>"><?php echo esc_html( $sec['name'] ); ?></a>
		<?php endforeach; ?>
	</div>
</nav>

<div class="sk-menu">
	<?php if ( $tips ) : ?>
		<section class="sk-mtips" id="tipy" aria-labelledby="sk-h-tipy">
			<div class="sk-mwrap">
				<h2 id="sk-h-tipy" class="sk-mgroup-title">Tipy z&nbsp;kavárny</h2>
				<div class="sk-mtips-row">
					<?php foreach ( $tips as $n => $it ) : ?>
						<article class="sk-mtip sk-mtip--<?php echo (int) ( $n % 4 ) + 1; ?>">
							<div class="sk-mtip-pic"><?php echo $sk_thumb( $it, 'medium_large', '(max-width: 700px) 62vw, 260px', $n < 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<div class="sk-mtip-body">
								<?php if ( $it['badge'] ) : ?><span class="sk-mbadge <?php echo esc_attr( skalicka_menu_badge_class( $it['badge'] ) ); ?>"><?php echo esc_html( $it['badge'] ); ?></span><?php endif; ?>
								<h3><?php echo esc_html( $it['name'] ); ?></h3>
								<?php if ( $it['desc'] ) : ?><p><?php echo skalicka_nbsp( esc_html( $it['desc'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p><?php endif; ?>
							</div>
							<?php if ( '' !== $it['price'] ) : ?><span class="sk-mtip-price"><?php echo skalicka_menu_price_html( $it['price'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php foreach ( $groups as $gkey => $gname ) : ?>
		<?php
		$secs = array_values( array_filter( $data, function ( $s ) use ( $gkey ) {
			return $s['group'] === $gkey;
		} ) );
		if ( ! $secs ) {
			continue;
		}
		?>
		<div class="sk-mgroup sk-mgroup--<?php echo esc_attr( $gkey ); ?>">
			<div class="sk-mwrap">
				<h2 class="sk-mgroup-title"><?php echo esc_html( $gname ); ?></h2>
				<div class="sk-mgrid">
					<?php foreach ( $secs as $sec ) : ?>
						<section class="sk-msec<?php echo $sec['box'] ? ' sk-msec--' . esc_attr( $sec['box'] ) : ''; ?>" id="<?php echo esc_attr( $sec['slug'] ); ?>" aria-labelledby="sk-h-<?php echo esc_attr( $sec['slug'] ); ?>">
							<h3 id="sk-h-<?php echo esc_attr( $sec['slug'] ); ?>"><?php echo esc_html( $sec['name'] ); ?></h3>
							<?php if ( $sec['note'] ) : ?><p class="sk-msec-note"><?php echo skalicka_nbsp( esc_html( $sec['note'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p><?php endif; ?>
							<ul class="sk-mlist">
								<?php foreach ( $sec['items'] as $it ) : ?>
									<?php
									$big = $it['photo'] ? wp_get_attachment_image_url( $it['photo'], 'medium_large' ) : '';
									?>
									<li class="sk-mitem<?php echo $it['photo'] ? ' has-foto' : ''; ?>">
										<?php if ( $big ) : ?>
											<button type="button" class="sk-mthumb" data-sk-foto="<?php echo esc_url( $big ); ?>" aria-label="Fotka: <?php echo esc_attr( $it['name'] ); ?>"><?php echo $sk_thumb( $it, 'medium', '84px' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
										<?php else : ?>
											<span class="sk-mthumb is-ikona"><?php echo $sk_thumb( $it, 'medium', '84px' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
										<?php endif; ?>
										<div class="sk-mtext">
											<span class="sk-mname"><?php echo esc_html( $it['name'] ); ?></span>
											<?php if ( $it['badge'] ) : ?><span class="sk-mbadge <?php echo esc_attr( skalicka_menu_badge_class( $it['badge'] ) ); ?>"><?php echo esc_html( $it['badge'] ); ?></span><?php endif; ?>
											<?php if ( $it['desc'] ) : ?><span class="sk-mdesc"><?php echo skalicka_nbsp( esc_html( $it['desc'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
										</div>
										<?php if ( '' !== $it['price'] ) : ?><span class="sk-mprice"><?php echo skalicka_menu_price_html( $it['price'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<section class="sk-minfo" aria-label="Dobré vědět">
		<div class="sk-mwrap">
			<ul class="sk-minfo-list">
				<li><b>Ceny</b> jsou v&nbsp;Kč včetně DPH.</li>
				<li><b>S&nbsp;sebou</b> vám jídlo zabalíme do krabičky za 10&nbsp;Kč.</li>
				<li><b>Alergeny</b> vám řekne obsluha na baru.</li>
			</ul>
		</div>
	</section>

	<section class="sk-mcta" aria-label="Členství a oslavy">
		<div class="sk-mwrap sk-mcta-grid">
			<div class="sk-mcta-card sk-mcta-card--clen">
				<p class="sk-mcta-kicker">Chodíte k&nbsp;nám často?</p>
				<h2>S&nbsp;členstvím ušetříte</h2>
				<p>Neomezený vstup pro dítě za 549&nbsp;Kč měsíčně, lekce a&nbsp;akce zdarma a&nbsp;<strong>10&nbsp;% z&nbsp;celého účtu v&nbsp;kavárně</strong>.</p>
				<a class="sk-btn" href="<?php echo esc_url( $clenstvi ); ?>">Jak členství funguje</a>
			</div>
			<div class="sk-mcta-card sk-mcta-card--oslava">
				<p class="sk-mcta-kicker">Slavíte narozeniny?</p>
				<h2>Oslava v&nbsp;herně</h2>
				<p>Stůl pro 8–10 dětí, vlastní salonek nebo celá herna. Připravíme a&nbsp;vyzdobíme, k&nbsp;oslavě objednáte i&nbsp;obložené mísy.</p>
				<a class="sk-btn" href="<?php echo esc_url( $oslavy ); ?>">Prohlédnout oslavy</a>
			</div>
		</div>
	</section>
</div>

<dialog class="sk-mfoto" id="sk-mfoto" aria-label="Fotka z menu">
	<form method="dialog"><button class="sk-mfoto-x" aria-label="Zavřít">×</button></form>
	<img src="" alt="" decoding="async">
	<p class="sk-mfoto-name"></p>
</dialog>

<?php endif; ?>
<?php
get_footer();
