/* Skalička: stránka Menu – lišta sekcí (zvýraznění podle místa na stránce) a fotka položky na celou obrazovku. */
(function () {
	'use strict';

	var nav = document.getElementById('sk-mnav');
	if (nav) {
		var links = [].slice.call(nav.querySelectorAll('a[href^="#"]'));
		var targets = links.map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); });
		var current = null;

		var setActive = function (i) {
			if (i === current) { return; }
			current = i;
			links.forEach(function (a, n) {
				if (n === i) { a.setAttribute('aria-current', 'true'); } else { a.removeAttribute('aria-current'); }
			});
			var a = links[i];
			if (a) {
				var box = nav.firstElementChild;
				var left = a.offsetLeft - (box.clientWidth - a.offsetWidth) / 2;
				box.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
			}
		};

		var barTop = function () {
			var bar = document.getElementById('wpadminbar');
			return bar && getComputedStyle(bar).position === 'fixed' ? bar.offsetHeight : 0;
		};
		var offset = function () { return barTop() + nav.offsetHeight + 24; };

		var onScroll = function () {
			var y = offset();
			var idx = 0;
			targets.forEach(function (t, n) {
				if (t && t.getBoundingClientRect().top - y <= 0) { idx = n; }
			});
			setActive(idx);
			nav.classList.toggle('is-stuck', window.pageYOffset > 0 && nav.getBoundingClientRect().top <= barTop() + 1);
		};

		var ticking = false;
		window.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(function () { onScroll(); ticking = false; });
			}
		}, { passive: true });
		window.addEventListener('resize', onScroll);
		onScroll();

		nav.addEventListener('click', function (e) {
			var a = e.target.closest('a[href^="#"]');
			if (!a) { return; }
			var t = document.getElementById(a.getAttribute('href').slice(1));
			if (!t) { return; }
			e.preventDefault();
			var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			window.scrollTo({ top: t.getBoundingClientRect().top + window.pageYOffset - offset() + 12, behavior: reduce ? 'auto' : 'smooth' });
			if (history.replaceState) { history.replaceState(null, '', '#' + t.id); }
		});
	}

	/* Fotka položky v okně. */
	var dlg = document.getElementById('sk-mfoto');
	if (dlg && typeof dlg.showModal === 'function') {
		var img = dlg.querySelector('img');
		var name = dlg.querySelector('.sk-mfoto-name');
		document.addEventListener('click', function (e) {
			var b = e.target.closest('[data-sk-foto]');
			if (!b) { return; }
			var item = b.closest('.sk-mitem');
			var title = item ? item.querySelector('.sk-mname') : null;
			var price = item ? item.querySelector('.sk-mprice') : null;
			img.src = b.getAttribute('data-sk-foto');
			img.alt = title ? title.textContent : '';
			name.textContent = (title ? title.textContent : '') + (price ? ' · ' + price.textContent.replace(/\s+/g, ' ').trim() : '');
			dlg.showModal();
		});
		dlg.addEventListener('click', function (e) {
			if (e.target === dlg) { dlg.close(); }
		});
		dlg.addEventListener('close', function () { img.removeAttribute('src'); });
	} else {
		/* Starší prohlížeč bez <dialog>: fotku nezvětšovat. */
		[].forEach.call(document.querySelectorAll('[data-sk-foto]'), function (b) { b.removeAttribute('data-sk-foto'); b.setAttribute('tabindex', '-1'); });
	}
})();
