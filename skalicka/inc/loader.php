<?php
/**
 * Načítání s logem: překryv se ukáže jen tehdy, když stránka nenaběhne do 0,5 s,
 * nebo hned po kliknutí na odkaz v rámci webu. Rychlé stránky nijak nezdržuje.
 * Logo bere z ikony webu (Nastavení → Obecné → Ikona webu).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skalicka_loader_logo() {
	$id = (int) get_option( 'site_icon' );
	if ( ! $id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $id, 'medium' );
	return $url ? $url : (string) wp_get_attachment_url( $id );
}

add_action( 'wp_body_open', function () {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}
	$logo = skalicka_loader_logo();
	if ( ! $logo ) {
		return;
	}
	?>
	<div class="sk-loader" id="sk-loader" aria-hidden="true">
		<div class="sk-loader-in"><img src="<?php echo esc_url( $logo ); ?>" width="150" height="150" alt="" decoding="async" fetchpriority="low"></div>
	</div>
	<script>
	(function(){
		var el=document.getElementById('sk-loader'),done=false,t;
		function on(){if(!done){el.classList.add('is-on');}}
		function off(){done=true;clearTimeout(t);el.classList.remove('is-on');}
		t=setTimeout(on,500);
		window.addEventListener('load',off);
		setTimeout(off,3000);
		window.addEventListener('pageshow',function(e){if(e.persisted){off();}});
		document.addEventListener('click',function(e){
			if(e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey){return;}
			var a=e.target.closest&&e.target.closest('a[href]');
			if(!a||a.target&&a.target!=='_self'||a.hasAttribute('download')){return;}
			var u;try{u=new URL(a.href,location.href);}catch(x){return;}
			if(u.origin!==location.origin||/^\/wp-(admin|login)/.test(u.pathname)||/\.(pdf|jpe?g|png|webp|zip)$/i.test(u.pathname)){return;}
			if(u.pathname===location.pathname&&u.search===location.search){return;}
			done=false;clearTimeout(t);t=setTimeout(on,150);setTimeout(off,8000);
		});
	})();
	</script>
	<?php
}, 1 );
