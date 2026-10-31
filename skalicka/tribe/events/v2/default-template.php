<?php
/**
 * Kalendář (The Events Calendar) v barvách Skaličky: modrá hlavička, mátový pás a karta s kalendářem.
 * Přepisuje the-events-calendar/src/views/v2/default-template.php.
 */

use Tribe\Events\Views\V2\Template_Bootstrap;

get_header();

$sk_single = is_singular( 'tribe_events' );
?>
<div class="sk-pagehead sk-pagehead--bands">
	<div class="sk-wrap">
		<?php if ( $sk_single ) : ?>
			<div class="sk-crumb"><a href="<?php echo esc_url( skalicka_calendar_url() ); ?>">← Kalendář akcí</a></div>
		<?php endif; ?>
		<h1><?php echo $sk_single ? esc_html( get_the_title() ) : 'Kalendář akcí'; ?></h1>
		<?php echo skalicka_page_art( 'kalendar' ); // phpcs:ignore WordPress.Security.EscapeOutput -- pevné SVG ?>
	</div>
</div>
<div class="sk-pbands">
	<section class="sk-pband sk-bg-mint sk-cal-band">
		<div class="sk-page sk-pband-in">
			<?php if ( ! $sk_single ) : ?>
				<ul class="sk-cal-legend" aria-label="Vysvětlivky">
					<li class="is-dnes">Dnes</li>
					<li class="is-akce">Akce, máme otevřeno</li>
					<li class="is-zavreno">Zavřeno</li>
				</ul>
			<?php endif; ?>
			<div class="sk-cal">
				<?php echo tribe( Template_Bootstrap::class )->get_view_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
	</section>
</div>
<?php
get_footer();
