<?php
/**
 * Běžná stránka: modrá hlavička s nadpisem a obsah.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$parent = wp_get_post_parent_id( get_the_ID() );
	?>
	<div class="sk-pagehead<?php echo skalicka_is_banded_page() ? ' sk-pagehead--bands' : ''; ?>">
		<div class="sk-wrap">
			<?php if ( $parent ) : ?>
				<div class="sk-crumb"><a href="<?php echo esc_url( get_permalink( $parent ) ); ?>">← <?php echo esc_html( get_the_title( $parent ) ); ?></a></div>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<?php echo skalicka_page_art( get_post_field( 'post_name', get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- pevné SVG ?>
		</div>
		<?php if ( ! skalicka_is_banded_page() ) { skalicka_wave(); } ?>
	</div>
	<?php skalicka_page_content(); ?>
	<?php
endwhile;

get_footer();
