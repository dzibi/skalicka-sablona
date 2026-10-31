<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text sk-skip" href="#obsah">Přeskočit na obsah</a>
<header class="sk-header" id="sk-header">
	<div class="sk-wrap">
		<?php
		$sk_semafor = '<span class="sk-semafor" role="status" aria-live="polite" data-stav=""><span class="sk-dot" aria-hidden="true"></span><span class="sk-semafor-text">' . esc_html( skalicka_hours_short() ) . '</span></span>';
		if ( is_front_page() ) :
			?>
			<a class="sk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> – hlavní stránka">
				<?php
				$logo_id = get_theme_mod( 'custom_logo' );
				if ( $logo_id ) {
					echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'alt' => get_bloginfo( 'name' ) ) );
				} else {
					echo '<span class="sk-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
				}
				?>
			</a>
			<?php
			$sk_icon = (int) get_option( 'site_icon' );
			$sk_icon = $sk_icon ? wp_get_attachment_image_url( $sk_icon, 'thumbnail' ) : '';
			if ( $sk_icon ) :
				?>
				<img class="sk-head-logo" src="<?php echo esc_url( $sk_icon ); ?>" width="64" height="64" alt="" decoding="async">
			<?php endif; ?>
			<?php echo $sk_semafor; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>
			<?php echo $sk_semafor; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<a class="sk-logo sk-home" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Domů – hlavní stránka <?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<span class="sk-logo-text"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M10 19.5v-5h4v5"/></svg>Domů</span>
			</a>
		<?php endif; ?>
		<nav class="sk-nav" id="sk-nav" aria-label="Hlavní menu">
			<?php skalicka_render_menu(); ?>
		</nav>
		<details class="sk-rez">
			<summary>Rezervovat</summary>
			<?php skalicka_reservation_menu(); ?>
		</details>
		<button class="sk-menu-toggle" type="button" aria-controls="sk-nav" aria-expanded="false" aria-label="Otevřít menu">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
		</button>
	</div>
</header>
<main id="obsah">
