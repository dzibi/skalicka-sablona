<?php
/**
 * Záložní šablona (novinky, archivy, kalendář akcí).
 */
get_header();
?>
<div class="sk-pagehead">
	<div class="sk-wrap"><h1><?php echo esc_html( is_singular() ? get_the_title() : wp_strip_all_tags( get_the_archive_title() ) ); ?></h1></div>
	<?php skalicka_wave(); ?>
</div>
<div class="sk-page">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			if ( is_singular() ) {
				the_content();
			} else {
				echo '<h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';
				the_excerpt();
			}
		}
		the_posts_pagination();
	} else {
		echo '<p>Nic tu zatím není.</p>';
	}
	?>
</div>
<?php
get_footer();
