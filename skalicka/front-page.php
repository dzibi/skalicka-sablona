<?php
/**
 * Hlavní stránka: obsah stránky nastavené jako úvodní, jinak stránky „Domů“ (adresa domu).
 */
get_header();

$front_id = (int) get_option( 'page_on_front' );
$front    = $front_id ? get_post( $front_id ) : get_page_by_path( 'domu' );
?>
<div class="sk-front">
	<?php
	if ( $front ) {
		global $post;
		$post = $front; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		setup_postdata( $post );
		the_content();
		wp_reset_postdata();
	} else {
		echo '<div class="sk-page"><h1>' . esc_html( get_bloginfo( 'name' ) ) . '</h1><p>Naimportujte obsah webu (Nástroje → Import).</p></div>';
	}
	?>
</div>
<?php
get_footer();
